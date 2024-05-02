<?php

namespace Aznoqmous\ContaoMultilangBundle\Controller;

use Aznoqmous\ContaoMultilangBundle\Multilang\DcaMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\Model;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MultilangController
 * @Route("/api/multilang", defaults={"_scope" = "backend", "_token_check" = false}, name="api_multilang_")
 * @package Aznoqmous\ContaoEcommerceBundle\Controller
 */
class MultilangController extends AbstractController
{
    /**
     * Set language for all entities without multilang_lang
     * @Route("/set/{langKey}", name="set_language")
     */
    public function setLanguage($langKey)
    {
        $this->initializeContaoFramework();
        $res = [];
        foreach (DcaMultilang::getMultilangDCTables() as $configuration) {
            $res[] = EntityMultilang::setTableLanguage($configuration->getTable(), $langKey)->getModels();
        }
        return $this->json($res);
    }

    /**
     * Set language for all entities without multilang_lang inside $table
     * @Route("/set/{langKey}/{table}", name="set_table_language")
     */
    public function setTableLanguage($langKey, $table)
    {
        return $this->json(EntityMultilang::setTableLanguage($table, $langKey)->getModels());
    }

    /**
     * Set language for entity $id inside $table
     * @Route("/set/{langKey}/{table}/{id}", name="set_entity_language")
     */
    public function setEntityLanguage($langKey, $table, $id)
    {
        $model = Model::getClassFromTable($table);
        $entity = $model::findOneById($id);
        if (!$entity) return $this->json(false);
        $entity->multilang_lang = $langKey;
        $entity->save();
        return $this->json($entity);
    }

    /**
     * @Route("/variants/{table}/{id}")
     */
    public function getLangVariants($table, $id)
    {
        $model = Model::getClassFromTable($table);
        $entity = $model::findOneById($id);
        $variants = EntityMultilang::getLangModels($entity);
        if (!$variants) return $this->json([]);
        $variants = array_map(function ($v) {
            $language = Multilang::getLanguageByKey($v->multilang_lang);
            return [
                'id' => $v->id,
                'lang' => $v->multilang_lang,
                'label' => $language->label,
                'flag' => $language->getImagePath()
            ];
        }, $variants->getModels());
        return $this->json($variants);
    }

    /**
     * @Route("/variants/{table}")
     */
    public function getLangVariantsForTable($table)
    {
        $request = Request::createFromGlobals();
        $ids = $request->request->get('ids');
        $model = Model::getClassFromTable($table);
        $models = [];
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $entity = $model::findOneById($id);
            $variants = EntityMultilang::getLangModels($entity);
            $models[$id] = array_map(function ($v) {
                $language = Multilang::getLanguageByKey($v->multilang_lang);
                return [
                    'id' => $v->id,
                    'lang' => $v->multilang_lang,
                    'label' => $language->label,
                    'flag' => $language->getImagePath()
                ];
            }, $variants->getModels());
        }
        return $this->json($models);
    }

    /**
     * Copy parent content inside variant
     * @Route("/parentCopy", "parent_copy")
     */
    public function parentCopy(){
        $request = Request::createFromGlobals();
        $redirectUrl = $request->request->get('redirectUrl');
        $table = $request->request->get('table');
        $id = $request->request->get('id');

        $model = Model::getClassFromTable($table);
        $variant = $model::findOneById($id);
        $parent = EntityMultilang::getLangReference($variant);

        $datas = $parent->row();
        unset($datas["id"]);
        unset($datas["pid"]);
        unset($datas["multilang_lang"]);
        unset($datas["multilang_pid"]);

        foreach($datas as $key => $value) $variant->{$key} = $value;
        $variant->save();

        return $this->redirect($redirectUrl);
    }

    /**
     * @Route("/languages")
     */
    public function getLanguages(){
        $this->initializeContaoFramework();
        return $this->json(Multilang::getLanguages());
    }

}
