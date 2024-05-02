<?php

namespace Aznoqmous\ContaoMultilangBundle\Models;

use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\Input;

trait MultilangModelTrait
{

    public function __construct($data)
    {
        parent::__construct($data);
        if (TL_MODE == "FE" && !Input::post('changeLang')) $this->getActiveLanguageVariantData();
        return $this;
    }

    public function getActiveLanguageVariantData()
    {
        $langKey = Multilang::getActiveLanguageKey();
        $variant = EntityMultilang::getLangVariant($this, $langKey);
        if ($variant) $this->setRow($variant->row());
    }

    protected static function find(array $arrOptions)
    {
        if (Input::post('changeLang')) return parent::find($arrOptions);
        if (TL_MODE == "FE" && Multilang::getActiveLanguageKey()) {
            if (!$arrOptions['column']){
              $arrOptions['column'] = [];
            }
            else {
                if (!is_array($arrOptions['column'])) {
                    $arrOptions['column'] = ["{$arrOptions['column']} = '{$arrOptions['value']}'"];
                    $arrOptions['value'] = [];
                }
            }
            $arrOptions['column'][] = self::getTable(). '.multilang_lang = "' . Multilang::getActiveLanguageKey() . '"';
        }
        return parent::find($arrOptions);
    }

}
