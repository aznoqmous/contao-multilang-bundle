<?php

namespace Aznoqmous\ContaoMultilangBundle\EventListener;

use Aznoqmous\ContaoMultilangBundle\Event\FrontendMultilangRedirectEvent;
use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\NewsBundle\ContaoNewsBundle;

class FrontendMultilangRedirectListener
{
    /**
     * Exemple RedirectListener for ContaoNewsBundle
     * Search for newsreader modules on active page
     * Rewrite url to /translatedPageUrl/translateNewsAlias
     * @param FrontendMultilangRedirectEvent $event
     * @return void|null
     */
    public function __invoke(FrontendMultilangRedirectEvent $event)
    {
        if(!class_exists(ContaoNewsBundle::class)) return;
        if($event->isPropagationStopped()) return;

        $modules = Multilang::findModuleInCurrentPageByType("news_reader");
        if (!$modules) return;

        $news = \Contao\NewsModel::findOneByAlias(basename($_SERVER['REQUEST_URI']));
        $translatedNews = EntityMultilang::getLangVariant($news, $event->targetLanguage->key);
        if(!$translatedNews) return null;

        /* Stop propagation if matched */
        $event->stopPropagation();
        $event->targetUrl = $event->targetPage->getFrontendUrl() . "/" . $translatedNews->alias;
    }

}