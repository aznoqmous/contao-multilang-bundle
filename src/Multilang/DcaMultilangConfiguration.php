<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

class DcaMultilangConfiguration
{

    private $table;

    private $childTables = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(){
        return $this->table;
    }

    public function addChildTable(string $table){
        $this->childTables[] = $table;
        $this->childTables = array_values(array_unique($this->childTables));
    }

    public function getChildTables(){
        return $this->childTables;
    }

}