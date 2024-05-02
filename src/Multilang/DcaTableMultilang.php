<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Contao\Input;
use Contao\Model;
use Contao\System;

final class DcaTableMultilang extends DcaMultilang {

    public function __construct($table)
    {
        parent::__construct($table);
        $GLOBALS['TL_DCA'][$table]['config']['multilang'] = true;
        $GLOBALS['TL_DCA'][$table]['fields']['multilang_pid'] = ['sql' => "int(10) unsigned default null"];
        $GLOBALS['TL_DCA'][$table]['fields']['multilang_lang'] = [
            'inputType' => "multilangHiddenField",
            'exclude' => true,
            'sql' => "varchar(2) unsigned default null"
        ];

        if (Multilang::hasLanguages()) {
            $GLOBALS['TL_DCA'][$table]['list']['sorting']['filter'][] = ['multilang_lang=?', Multilang::getActiveLanguageKey()];
            $this->addEditVariantButton();
            $GLOBALS['TL_DCA'][$table]['select']['buttons_callback'][] = fn($arrButtons) => $this->addGenerateVariantsButton($arrButtons);
        }

        if(is_array($GLOBALS['TL_DCA'][$table]['palettes'])) foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as &$palette) {
            if (is_string($palette)) $palette = "multilang_lang;" . $palette;
        }

        // rebind to parent following reference structure
        $this->addSubmitCallback(function ($dca) use ($table) {
            $model = Model::getClassFromTable($table);
            $model = $model::findOneById($dca->activeRecord->id);
            EntityMultilang::updateVariantsPid($model);
        });
    }

    public static function set($table)
    {
        $instance = new self($table);
        return $instance;
    }

    /**
     * Add a table rule to automatically duplicate current $this->table children from given $strTable
     * eg: DcaMultilang::setTable("tl_page")->addChildrenRule("tl_article")
     * @param $strTable
     * @return $this
     */
    public function addChildrenTable($strTable)
    {
        $model = Model::getClassFromTable($strTable);

        $this->configuration->addChildTable($strTable);

        $this->addTableRule(function ($variant, $reference) use ($model) {
            $referenceContents = $model::findByPid($reference->id);
            if ($referenceContents) foreach ($referenceContents as $content) EntityMultilang::createLangVariant($content, $variant->multilang_lang);
        });
        return $this;
    }

    public function addGenerateVariantsButton($arrButtons)
    {
        if (isset($_POST['generateVariants']) && Input::post('FORM_SUBMIT') == 'tl_select')
        {
            $objSession = System::getContainer()->get('session');
            $session = $objSession->all();
            $ids = $session['CURRENT']['IDS'];
            $model = Model::getClassFromTable($this->table);
            $models = $model::findMultipleByIds($ids);
            foreach($models as $model){
                EntityMultilang::createLangVariant($model, $_POST['generateVariants']);
            }
            BackendMultilang::rewriteUrlParameters([
                'act' => null,
                'lang' => $_POST['generateVariants']
            ]);
        }

        foreach(Multilang::getLanguages() as $language){
            if($language == Multilang::getActiveLanguage()) continue;
            $arrButtons["generateVariants"] =
                '<button type="submit" name="generateVariants" value="' . $language->key . '" id="generateVariants" class="tl_submit">' . $GLOBALS['TL_LANG']['multilang']['generateVariants'] . '&nbsp;<img src="' . $language->getImagePath() . '"></button> '
            ;
        }

        return $arrButtons;
    }

    public function addEditVariantButton(){
        $operations = [];
        $dcaOperations = $GLOBALS['TL_DCA'][$this->table]['list']['operations'];
        if(!is_array($dcaOperations)) return;
        foreach($dcaOperations as $key => $value){
            $operations[$key] = $value;
            if($key == 'edit') {
                foreach(Multilang::getLanguages() as $language){
                    if($language == Multilang::getActiveLanguage()) continue;
                    $operations["editVariant_$language->key"] = [
                        'button_callback' => fn($row, $href, $label, $title, $icon, $attributes)=> $this->editVariantButton($row, $href, $label, $title, $icon, $attributes, $language)
                    ];
                }
            }
        }
        $GLOBALS['TL_DCA'][$this->table]['list']['operations'] = $operations;
    }

    public function editVariantButton($row, $href, $label, $title, $icon, $attributes, $language){
        $modelClass = Model::getClassFromTable($this->table);
        $model = $modelClass::findOneBy('id', $row['id']);
        $existingTranslation = EntityMultilang::getLangVariant($model, $language->key);
        $className = $existingTranslation ? "edit" : "create";

        $href = $GLOBALS['TL_DCA'][$this->table]['list']['operations']['edit']['href'];
        $isParent = $GLOBALS['TL_DCA'][$this->table]['list']['operations']['editheader'];
        if(!$existingTranslation && $isParent){
            $href = $GLOBALS['TL_DCA'][$this->table]['list']['operations']['editheader']['href'];
        }

        $splittedHref = explode('=', $href);
        $params = http_build_query(array_merge($_GET, [
            $splittedHref[0] => $splittedHref[1],
            "id" => $model->id,
            "lang" => $language->key,
            "rt" => REQUEST_TOKEN
        ]));
        return "<span class='table-lang-variant $className'><a href=\"/contao?$params\">{$language->getFlagImage()}</a></span>";
    }

}
