<?php

namespace Aznoqmous\ContaoMultilangBundle\Event;

use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Language;
use Symfony\Contracts\EventDispatcher\Event;

class FrontendMultilangRedirectEvent extends Event
{
    public $targetLanguage;

    public $currentPage;
    public $targetPage;

    public $targetUrl;

    public function __construct(Language $targetLanguage)
    {
        global $objPage;
        $this->targetLanguage = $targetLanguage;
        $this->currentPage = $objPage;
        $this->targetPage = EntityMultilang::getLangVariant($objPage, $targetLanguage->key);
        $this->targetUrl = $this->targetPage ? $this->targetPage->getFrontendUrl() : $this->currentPage->getFrontendUrl();
    }
}