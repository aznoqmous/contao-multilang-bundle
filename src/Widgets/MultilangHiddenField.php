<?php

namespace Aznoqmous\ContaoMultilangBundle\Widgets;

use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\Widget;

/**
 * Automatically added via DcaMultilang->setTable()
 */
class MultilangHiddenField extends Widget
{
    protected $blnSubmitInput = true;

    protected $strTemplate = "be_multilang_hidden_field";

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
        $this->varValue = $_GET['act'] != "editAll" ? Multilang::getActiveLanguageKey() : $this->varValue;
        $this->language = Multilang::getLanguageByKey($this->varValue);
    }

    public function generate()
    {
    }
}