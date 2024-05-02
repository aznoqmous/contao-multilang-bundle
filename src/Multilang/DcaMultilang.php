<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Contao\Config;
use Contao\Controller;
use Contao\Model;

/**
 * Handles TL_DCA manipulations
 */
class DcaMultilang
{
    protected string $table;
    protected DcaMultilangConfiguration $configuration;

    public function __construct($table)
    {
        $this->table = $table;
        Controller::loadDataContainer($table);
        $this->configuration = Multilang::getInstance()->addTable($table);
    }

    public static function getMultilangDCTables()
    {
        $tables = array_filter(Multilang::getInstance()->getTables(), function ($configuration) {
            $table = $configuration->getTable();
            Controller::loadDataContainer($table);
            return preg_match("/Table/", $GLOBALS['TL_DCA'][$table]['config']['dataContainer']);
        });
        return $tables;
    }

    public function addSubmitCallback($callback)
    {
       $GLOBALS['TL_DCA'][$this->table]['config']['onsubmit_callback'][] = $callback;
        return $this;
    }

    public function addCreateCallback($callback)
    {
        $GLOBALS['TL_DCA'][$this->table]['config']['oncreate_callback'][] = $callback;
        return $this;
    }

    public function addLoadCallback($callback)
    {
        $GLOBALS['TL_DCA'][$this->table]['config']['onload_callback'][] = $callback;
        return $this;
    }

    /**
     * Field rules are used when creating a new variant
     * @param $callback
     * @return void
     */
    public static function addInputTypeRule($field, $callback)
    {
        if (!is_array($GLOBALS['TL_HOOKS']['multilangInputTypeRules'])) $GLOBALS['TL_HOOKS']['multilangInputTypeRules'] = [];
        if (!is_array($GLOBALS['TL_HOOKS']['multilangInputTypeRules'][$field])) $GLOBALS['TL_HOOKS']['multilangInputTypeRules'][$field] = [];
        $GLOBALS['TL_HOOKS']['multilangInputTypeRules'][$field][] = $callback;
    }

    public static function getInputTypeRules()
    {
        return $GLOBALS['TL_HOOKS']['multilangInputTypeRules'];
    }

    /**
     * Field rules are used when creating a new variant
     * @param $callback
     * @return void
     */
    public static function addFieldRule($field, $callback)
    {
        if (!is_array($GLOBALS['TL_HOOKS']['multilangFieldRules'])) $GLOBALS['TL_HOOKS']['multilangFieldRules'] = [];
        if (!is_array($GLOBALS['TL_HOOKS']['multilangFieldRules'][$field])) $GLOBALS['TL_HOOKS']['multilangFieldRules'][$field] = [];
        $GLOBALS['TL_HOOKS']['multilangFieldRules'][$field][] = $callback;
    }

    public static function getFieldRules()
    {
        return $GLOBALS['TL_HOOKS']['multilangFieldRules'];
    }

    /**
     * Field rules are used when creating a new variant of given strTable
     * @param $strTable
     * @param $callback
     * @return $this
     */
    public function addTableRule($callback)
    {
        if (!is_array($GLOBALS['TL_HOOKS']['multilangTableRules'])) $GLOBALS['TL_HOOKS']['multilangTableRules'] = [];
        if (!is_array($GLOBALS['TL_HOOKS']['multilangTableRules'][$this->table])) $GLOBALS['TL_HOOKS']['multilangTableRules'][$this->table] = [];
        $GLOBALS['TL_HOOKS']['multilangTableRules'][$this->table][] = $callback;
        return $this;
    }

    public static function getTableRules()
    {
        return $GLOBALS['TL_HOOKS']['multilangTableRules'];
    }

}
