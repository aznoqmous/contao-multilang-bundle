<?php


namespace Aznoqmous\ContaoMultilangBundle\Widgets;

use Aznoqmous\ContaoMultilangBundle\Config\MultilangConfig;
use Aznoqmous\ContaoMultilangBundle\Multilang\DcaMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\Controller;
use Contao\Model;
use Contao\System;
use Contao\Widget;

/**
 * Automatically added via DcaMultilang->setTable()
 */
class MultilangTablesWizard extends Widget
{
    protected $strTemplate = "be_multilang_tables_wizard";

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        /* filter dc tables */
        $tables = [];
        $totalMissingEntities = 0;
        foreach(DcaMultilang::getMultilangDCTables() as $configuration){
            $table = $configuration->getTable();
            $missingEntities = EntityMultilang::getTableUndefinedLangEntities($table);
            $fields = ['id'];
            if($missingEntities){
                $entity = $missingEntities[0]->row();
                if(array_key_exists('title', $entity)) $fields[] = 'title';
                if(array_key_exists('name', $entity)) $fields[] = 'name';
                if(array_key_exists('type', $entity)) $fields[] = 'type';
                if(array_key_exists('alias', $entity)) $fields[] = 'alias';
                $totalMissingEntities += $missingEntities->count();
            }
            Controller::loadLanguageFile($table);

            $tables[] = (object)[
                'table' => $table,
                'label' =>  $GLOBALS['TL_LANG']['tl_multilang_settings'][$table],
                'missingLanguageEntities' => $missingEntities,
                'fields' => $fields
            ];
        }
        $this->totalMissingEntities = $totalMissingEntities;
        $this->tables = $tables;
    }

    public function generate()
    {
    }
}
