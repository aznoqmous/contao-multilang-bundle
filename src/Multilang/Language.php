<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

class Language {

    public $key;

    public $label;

    public $nativeLabel;

    public $imagePath;

    public function __construct($key, $label)
    {
        $this->key = $key;
        $splittedLabel = explode(" - ", $label);
        $this->label = ucfirst($splittedLabel[0]);
        $this->nativeLabel = ucfirst($splittedLabel[count($splittedLabel)-1]);
        $this->imagePath = $this->getImagePath();
    }

    public function getImagePath(){
        return "/bundles/contaomultilang/img/flag_icons/{$this->key}.png";
    }

    public function getFlagImage(){
        return "<img src='{$this->getImagePath()}'>";
    }

}
