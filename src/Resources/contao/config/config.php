<?php

use Aznoqmous\ContaoMultilangBundle\Multilang\DcaFileMultilang;
use Aznoqmous\ContaoMultilangBundle\Widgets\LangSelectionWizard;
use Aznoqmous\ContaoMultilangBundle\Widgets\MultilangHiddenField;
use Aznoqmous\ContaoMultilangBundle\Widgets\MultilangTablesWizard;


if(TL_MODE == "BE"){
    $GLOBALS['TL_JAVASCRIPT'][''] = "/bundles/contaomultilang/be.min.js";
    $GLOBALS['TL_CSS'][''] = "/bundles/contaomultilang/be.min.css";
}
else {
    $GLOBALS['TL_JAVASCRIPT'][''] = "/bundles/contaomultilang/fe.min.js";
    $GLOBALS['TL_CSS'][''] = "/bundles/contaomultilang/fe.min.css";
}

/* Models override to use MultilangModelTrait */
$GLOBALS['TL_MODELS'][\Contao\ModuleModel::getTable()] = \Aznoqmous\ContaoMultilangBundle\Models\ModuleModel::class;
$GLOBALS['TL_MODELS'][\Contao\FormModel::getTable()] = \Aznoqmous\ContaoMultilangBundle\Models\FormModel::class;

if(class_exists(\Contao\NewsBundle\ContaoNewsBundle::class)){
    $GLOBALS['TL_MODELS'][\Contao\NewsModel::getTable()] = \Aznoqmous\ContaoMultilangBundle\Models\NewsModel::class;
    $GLOBALS['TL_MODELS'][\Contao\NewsArchiveModel::getTable()] = \Aznoqmous\ContaoMultilangBundle\Models\NewsArchiveModel::class;
}

/* Custom backend widget */
$GLOBALS['BE_FFL']['multilangHiddenField'] = MultilangHiddenField::class;
$GLOBALS['BE_FFL']['multilangTablesWizard'] = MultilangTablesWizard::class;
$GLOBALS['BE_FFL']['langSelectionWizard'] = LangSelectionWizard::class;

/* Backend modules */
$GLOBALS['BE_MOD']['system']['multilang_settings'] = ['tables' => ['tl_multilang_settings']];
