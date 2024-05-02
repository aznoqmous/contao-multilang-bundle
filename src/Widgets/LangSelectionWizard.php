<?php


namespace Aznoqmous\ContaoMultilangBundle\Widgets;

use Aznoqmous\ContaoMultilangBundle\Config\MultilangConfig;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\System;
use Contao\Widget;

/**
 * Automatically added via DcaMultilang->setTable()
 */
class LangSelectionWizard extends Widget
{
    protected $strTemplate = "be_lang_selection_wizard";

    protected $blnSubmitInput = true;

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
        $this->selectedLanguages = Multilang::getLanguages();
        $availableLanguageKeys = Multilang::getLanguageKeys();
        $this->availableLanguages = array_filter(Multilang::getAvailableLanguages(), function ($lang) use ($availableLanguageKeys) {
            return !in_array($lang->key, $availableLanguageKeys);
        });
    }

    public function generate()
    {
    }
}
