<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;

/**
 * Utility class for multilang entities
 * Handle duplications
 */
class EntityMultilang
{
    public static function setTableLanguage($table, $langKey)
    {
        $noLangEntities = EntityMultilang::getTableUndefinedLangEntities($table);
        if(!$noLangEntities) return new Collection([], $table);
        foreach ($noLangEntities as $entity) {
            $entity->multilang_lang = $langKey;
            $entity->save();
        }
        return $noLangEntities;
    }

    public static function createLangVariants($model)
    {
        if (!self::isMultilangContent($model)) throw new \Exception("model is not a multilang content");

        $defaultLang = Multilang::getDefaultLanguage();

        $model->multilang_lang = $defaultLang->key;
        $model->multilang_pid = 0;
        $model->save();

        $model->getTable();

        foreach (Multilang::getLanguages() as $language) {
            $langKey = $language->key;
            if ($langKey === $defaultLang->key) continue;
            $variant = self::getLangVariant($model, $langKey);
            if (!$variant) {
                $variant = static::createLangVariant($model, $langKey);
                $variant->save();
            }
        }

    }

    /**
     * Update variants pid
     * @param $model
     * @return void
     */
    public static function updateVariantsPid($model, $parentModel=null){
        $variants = self::getLangVariants($model);
        if(!$variants) return;
        if(!$model->pid) return;

        $parentTable = $model->ptable ?: BackendMultilang::getParentTable($model->getTable());
        if(!$parentTable) $parentTable = $model->getTable();
        if(!Multilang::getInstance()->isTableMultilang($parentTable)) return;

        $parentModel = Model::getClassFromTable($parentTable);
        if(!$parentModel) $parentModel = $model;

        $parent = $parentModel::findOneById($model->pid);
        if(!$parent) return;

        foreach($variants as $variant){
            $parentVariant = self::getLangVariant($parent, $variant->multilang_lang);
            if(!$parentVariant) continue;
            if($variant->pid == $parentVariant->id) continue;
            $variant->pid = $parentVariant->id;
            $variant->save();
        }
    }

    public static function createLangVariant($model, $langKey)
    {
        $reference = self::getLangReference($model);
        $variant = self::getLangVariant($reference, $langKey);
        if(!$variant) {
            $variant = clone $model;
            $variant->multilang_pid = $reference->id;
            $variant->multilang_lang = $langKey;
            if(array_key_exists('published', $variant->row())) $variant->published = 0;
        }
        self::updateFieldsTranslation($variant);
        $variant->save();

        $tableRules = DcaMultilang::getTableRules();
        if(is_array($tableRules[$model->getTable()])){
            foreach($tableRules[$model->getTable()] as $callback){
                $callback($variant, $model);
            }
        }
        $variant->save();

        $parentTable = $reference->ptable ?: BackendMultilang::getParentTable($reference->getTable());
        if($parentTable){
            self::updateVariantsPid($reference, Model::getClassFromTable($parentTable));
        }

        return $variant;
    }

    public static function updateFieldsTranslation($model)
    {
        \Controller::loadDataContainer($model->getTable());
        $fields = $GLOBALS['TL_DCA'][$model->getTable()]['fields'];
        $inputTypeRules = DcaMultilang::getInputTypeRules();
        $fieldRules = DcaMultilang::getFieldRules();
        foreach($model->row() as $key => $value){
            $fieldDca = $fields[$key];
            $inputType = $fieldDca['inputType'];
            if(is_array($inputTypeRules[$inputType])){
                foreach($inputTypeRules[$inputType] as $callback) $callback($model, $key, $value, $fieldDca);
            }
            if(is_array($fieldRules[$key])){
                foreach($fieldRules[$key] as $callback) $callback($model, $key, $value, $fieldDca);
            }
        }
    }

    public static function isMultilangContent($model)
    {
        return array_key_exists('multilang_lang', $model->row());
    }

    public static function isReferenceEntity($model)
    {
        return !$model->multilang_pid;
    }

    public static function getLangReference($model){
        if(!$model->multilang_pid) return $model;
        return $model::findOneById($model->multilang_pid);
    }

    public static function getLangModels($model){
        $reference = self::getLangReference($model);
        if(!$reference) return new Collection([$model], $model->getTable());
        $models = self::getLangVariants($reference);
        $models = $models ? $models->getModels() : [];
        array_insert($models, 0, [$reference]);
        return new Collection($models, $model->getTable());
    }

    public static function getLangVariants($model){
        $reference = self::getLangReference($model);
        $table = $model->getTable();
        return $model::findBy([
           "$table.multilang_pid = {$reference->id}"
        ], []);
    }

    public static function getActiveLangVariant($model){
        return self::getLangVariant($model, Multilang::getActiveLanguageKey());
    }

    public static function getLangVariant($model, $langKey)
    {
        if($model->multilang_lang == $langKey) return $model;
        if(!$model) return null;

        $table = $model->getTable();
        if ($langKey == Multilang::getDefaultLanguageKey()) {
            if (!$model->multilang_pid) {
                return $model;
            } else {
                $variants = $model::findBy([
                    "$table.id = {$model->multilang_pid}"
                ], []);
            }

        } else {
            if (!$model->multilang_pid) {
                $variants = $model::findBy([
                    "$table.multilang_pid = {$model->id}",
                    "$table.multilang_lang = \"$langKey\""
                ], []);
            } else {
                $variants = $model::findBy([
                    "$table.multilang_pid = {$model->multilang_pid}",
                    "$table.multilang_lang = \"$langKey\""
                ], []);
            }
        }

        return $variants && $variants->count() ? $variants[0] : null;
    }

    public static function getMultipleLangVariant($models, $langKey){
        if(!$models || !$models->count()) return null;
        $variants = [];
        foreach($models as $model) {
            $variant = self::getLangVariant($model, $langKey);
            if($variant) $variants[] = $variant;
        }
        return new Collection($variants, $models[0]->getTable());
    }

    public static function getTableUndefinedLangEntities($table){
        $model = Model::getClassFromTable($table);
        return $model::findBy([
            "$table.multilang_lang is null OR $table.multilang_lang = \"\""
        ], []);
    }
}
