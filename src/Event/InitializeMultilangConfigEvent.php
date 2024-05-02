<?php

namespace Aznoqmous\ContaoMultilangBundle\Event;

use Aznoqmous\ContaoMultilangBundle\Multilang\DcaFileMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\DcaTableMultilang;
use Symfony\Contracts\EventDispatcher\Event;

class InitializeMultilangConfigEvent extends Event
{
    public function setTable($strTable):DcaTableMultilang
    {
        return DcaTableMultilang::set($strTable);
    }

    public function setFile($strTable):DcaFileMultilang
    {
        return DcaFileMultilang::set($strTable);
    }
}