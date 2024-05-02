<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;

class BackendMultilang
{
    public static function getActiveModel()
    {
        if (!$_GET['id']) return null;
        $table = self::getActiveTable();
        $model = Model::getClassFromTable($table);
        return $model::findOneById($_GET['id']);
    }

    public static function getActiveModels()
    {
        $table = self::getActiveTable();
        if (!$_GET['id']) return new Collection([], $table);
        $model = Model::getClassFromTable($table);
        $activeModel = $model::findOneById($_GET['id']);
        return $activeModel ? EntityMultilang::getLangModels($activeModel) : new Collection([], $table);
    }

    public static function renderLanguageSelect($submitOnChange = true)
    {
        $tpl = new BackendTemplate("be_language_select");
        $hiddenFields = [];
        foreach ($_GET as $key => $value) {
            if ($key == 'lang') continue;
            $hiddenFields[$key] = $value;
        }
        $tpl->hiddenFields = $hiddenFields;
        $tpl->activeLanguage = Multilang::getActiveLanguage();
        $tpl->submitOnChange = $submitOnChange;
        return $tpl->parse();
    }

    public static function renderLanguageSwitcher($submitOnChange = true)
    {
        $tpl = new BackendTemplate("be_language_switcher");
        $hiddenFields = [];
        foreach ($_GET as $key => $value) {
            if ($key == 'lang') continue;
            $hiddenFields[$key] = $value;
        }
        $tpl->existingLangs = self::getActiveModels()->fetchEach('multilang_lang');
        $tpl->hiddenFields = $hiddenFields;
        $tpl->activeLanguage = Multilang::getActiveLanguage();
        $tpl->submitOnChange = $submitOnChange;
        return $tpl->parse();
    }

    public static function renderLanguageFlagsSwitcher($submitOnChange = true)
    {
        $tpl = new BackendTemplate("be_language_flags_switcher");
        $hiddenFields = [];
        foreach ($_GET as $key => $value) {
            if ($key == 'lang') continue;
            $hiddenFields[$key] = $value;
        }
        $models = self::getActiveModels();
        $tpl->existingLangs = self::getActiveModels()->fetchEach('multilang_lang');
        $tpl->hiddenFields = $hiddenFields;
        $tpl->submitOnChange = $submitOnChange;
        if ($models) $tpl->referenceLang = EntityMultilang::getLangReference($models[0])->multilang_lang;

        return $tpl->parse();
    }

    public static function renderEditAll()
    {
        $models = self::getActiveModels();
        if (!$models->count()) return;

        $tpl = new BackendTemplate('be_edit_all');

        $tpl->action = http_build_query(array_merge($_GET, [
            'act' => 'select',
            'rt' => REQUEST_TOKEN
        ]));
        $tpl->hiddenFields = [
            'FORM_SUBMIT' => 'tl_select',
            'REQUEST_TOKEN' => REQUEST_TOKEN,
            'IDS[]' => $models->fetchEach('id'),
            'edit' => null
        ];
        return $tpl->parse();
    }

    public static function renderCopyParentContent(){
        $model = self::getActiveModel();
        $parentModel = EntityMultilang::getLangReference($model);

        if($model == $parentModel) return;

        $tpl = new BackendTemplate('be_copy_parent_content');

        $tpl->setData([
            "id" => $model->id,
            "table" => $model->getTable()
        ]);

        return $tpl->parse();
    }

    public static function handleCurrentBackendLanguage()
    {
        /* Apply lang to session */
        if ($_GET['lang']) {
            Multilang::setActiveLanguage($_GET['lang']);
            self::rewriteUrlParameters(['lang' => null]);
        }

        /* Set language to default if no language set in BE*/
        if(!Multilang::getActiveLanguage() && Multilang::getDefaultLanguage()){
            Multilang::setActiveLanguage(Multilang::getDefaultLanguageKey());
        }

        self::handleBreadCrumbRedirect();

        self::handleCreateVariant();
    }

    private static function handleBreadCrumbRedirect()
    {
        /* Redirect current filtered breadcrumb */
        $currentTable = self::getActiveTable();
        if (($currentTable == 'tl_article' || $currentTable == 'tl_page') && !$_GET['pn']) {
            $objSession = System::getContainer()->get('session')->getBag('contao_backend');
            $pageId = $objSession->get('tl_page_node');
            if (!$pageId) return;
            $page = PageModel::findOneById($pageId);
            $page = EntityMultilang::getLangVariant($page, Multilang::getActiveLanguageKey());
            if (!$page) return;
            if ($pageId == $page->id) return;
            \Aznoqmous\ContaoMultilangBundle\Multilang\BackendMultilang::rewriteUrlParameters([
                'pn' => $page->id
            ]);
        }
    }

    private static function handleCreateVariant()
    {
        /* On single entity = redirect to associated variant, create one if not exist */
        $currentTable = self::getActiveTable();

        if ($_GET['id'] && count($GLOBALS['TL_DCA'])) {
            if (!Multilang::getInstance()->isTableMultilang($currentTable)) return;
            $currentModel = Model::getClassFromTable($currentTable);
            $entity = $currentModel::findOneById($_GET['id']);
            if(!$entity) return;
            $langKey = Multilang::getActiveLanguageKey();
            $variant = EntityMultilang::getLangVariant($entity, $langKey);


            if (Input::get('act') == "edit" && !$variant) {

                $parentTable = self::getActiveParentTable();
                if($parentTable){
                    $parentModel = Model::getClassFromTable($parentTable);
                    $referenceParent = $parentModel::findOneById($entity->pid);
                    $parent = EntityMultilang::getLangVariant($referenceParent, $langKey);
                    if(!$parent) self::rewriteUrlParameters([
                        'id' => $referenceParent->id,
                        'table' => $parentTable,
                        'do' => self::getAllowedAction($parentTable, $_GET['do'])
                    ]);
                }

                $variant = EntityMultilang::createLangVariant($entity, Multilang::getActiveLanguageKey());
            }

            if ($variant && $variant != $entity) {
                self::rewriteUrlParameters([
                    'id' => $variant->id
                ]);
            }

        }
    }

    public static function isActiveTableMultilang()
    {
        return Multilang::getInstance()->isTableMultilang(self::getActiveTable());
    }

    public static function getAllowedAction($table, $currentAction){
        $categories = $GLOBALS['BE_MOD'];
        $actions = [];
        foreach ($categories as $category){
            foreach ($category as $action => $config){
                if(is_array($config['tables']) && in_array($table, $config['tables'])) $actions[] = $action;
            }
        }
        if(in_array($currentAction, $actions)) return $currentAction;
        return $actions[0];
    }

    public static function getActiveTable()
    {
        if (Input::get('table') && Input::get('act') == "edit") return Input::get('table');
        $module = $_GET['do'];
        $arrModule = array();

        foreach ($GLOBALS['BE_MOD'] as &$arrGroup) {
            if (isset($arrGroup[$module])) {
                $arrModule = &$arrGroup[$module];
                break;
            }
        }
        unset($arrGroup);

        $table = is_array($arrModule['tables']) ? $arrModule['tables'][0] : $arrModule['tables'];

        if(Input::get('table')){
            foreach(self::getTableTree($table) as $currentTable){
                if($currentTable == Input::get('table')) break;
                $table = $currentTable;
            }
        }

        return $table;
    }

    public static function getActiveDataContainerType(){
        $activeTable = self::getActiveTable();
        Controller::loadLanguageFile($activeTable);
        return $GLOBALS['TL_DCA'][$activeTable]['config']['dataContainer'];
    }

    public static function getActiveChildTable(){
        return Input::get('table') ?: self::getActiveTable();
    }

    public static function getActiveParentTable(){
        $activeChild = self::getActiveChildTable();
        $tableTree = self::getActiveTableTree();
        $previousTable = $tableTree[0];
        if($activeChild == $previousTable) return null;
        foreach($tableTree as $table){
            if($activeChild == $table) return $previousTable;
            $previousTable = $table;
        }
        return null;
    }

    public static function getActiveArrModule()
    {
        $module = $_GET['do'];
        $arrModule = array();

        foreach ($GLOBALS['BE_MOD'] as &$arrGroup) {
            if (isset($arrGroup[$module])) {
                $arrModule = &$arrGroup[$module];
                break;
            }
        }
        unset($arrGroup);
        return $arrModule;
    }

    public static function getTableTree($strTable, $tree = [])
    {
        if (!$strTable) return $tree;
        $tree[] = $strTable;
        Controller::loadDataContainer($strTable);
        $dca = $GLOBALS['TL_DCA'][$strTable];
        if ($dca['config']['ptable']) array_insert($tree, -1, $dca['config']['ptable']);
        if ($dca['config']['ctable']) return self::getTableTree($dca['config']['ctable'][0], $tree);
        return array_values(array_unique($tree));
    }

    public static function getActiveTableTree()
    {
        return self::getTableTree(self::getActiveTable());
    }

    public static function getParentTable(string $strTable)
    {
        $tree = self::getTableTree($strTable);
        $parent = $tree[0];
        foreach ($tree as $el) {
            if ($el == $strTable) break;
            $parent = $el;
        }
        return $parent;
    }

    public static function rewriteUrlParameters($params = [])
    {
        $params = array_merge($_GET, $params);
        header('Location: ' . $_SERVER['REDIRECT_URL'] . "?" . http_build_query($params));
        exit;
    }
}
