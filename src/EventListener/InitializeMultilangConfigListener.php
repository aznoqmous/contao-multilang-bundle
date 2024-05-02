<?php

namespace Aznoqmous\ContaoMultilangBundle\EventListener;

use Aznoqmous\ContaoMultilangBundle\Multilang\DcaMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\EntityMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Aznoqmous\ContaoMultilangBundle\Event\InitializeMultilangConfigEvent;
use Contao\ArticleModel;
use Contao\PageModel;

class InitializeMultilangConfigListener
{
    public function __invoke(InitializeMultilangConfigEvent $event)
    {
        /* DC_Tables */
        $event->setTable('tl_page')
            ->addChildrenTable('tl_page')
            ->addChildrenTable('tl_article')
            ->addSubmitCallback(function ($dca) {
                $page = PageModel::findOneById($dca->activeRecord->id);

                if (!$page->pid) {
                    /* Automatically set language + fallback fields */
                    $page->language = Multilang::getActiveLanguageKey();
                    if (Multilang::isDefaultLanguage()) {
                        $page->fallback = 1;
                    }
                    $page->save();
                }
                /* Apply the oncreate_callback articles' lang */
                $articles = ArticleModel::findByPid($page->id);
                if (!$articles) return;
                foreach ($articles as $article) {
                    $article->multilang_lang = $dca->activeRecord->multilang_lang;
                    $article->save();
                }
            });

        \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
            ->removeField('language')
            ->removeField('fallback')
            ->removeField('urlPrefix')
            ->removeField('urlSuffix')
            ->removeField('disableLanguageRedirect')
            ->applyToPalette('root', 'tl_page')
            ->applyToPalette('rootfallback', 'tl_page');

        $event->setTable('tl_article')
            ->addChildrenTable('tl_content')
        ;

        $event->setTable('tl_content');

        $event->setTable('tl_theme')
            ->addChildrenTable('tl_module');

        $event->setTable('tl_module');

        $event->setTable('tl_form')
            ->addChildrenTable('tl_form_field');

        $event->setTable('tl_form_field');


        /* Field duplication rules */
        DcaMultilang::addInputTypeRule('pageTree', function ($model, $key, $value, $field) {
            if (!$value) return;
            if ($field['eval']['multiple']) {
                $pageIds = unserialize($value);
                $pages = PageModel::findMultipleByIds($pageIds);
                if($pages && $pages->count()) {
                    $translatedPages = EntityMultilang::getMultipleLangVariant($pages, $model->multilang_lang);
                    $model->{$key} = serialize($translatedPages->fetchEach('id'));
                }
            } else {
                $page = PageModel::findOneById($value);
                if(!$page) return;
                $translatedPage = EntityMultilang::getLangVariant($page, $model->multilang_lang);
                $model->{$key} = $translatedPage ? $translatedPage->id : $value;
            }
        });
        DcaMultilang::addFieldRule('alias', function ($model, $key, $value, $field) {
            $aliasFields = \Aznoqmous\ContaoMultilangBundle\Config\MultilangConfig::getRequired('aliasFields');
            foreach ($aliasFields as $field) {
                if (!$model->{$field}) continue;
                $model->alias = Multilang::generateAlias($model, $model->title);
                break;
            }
        });


        /* News bundle */
        if (class_exists(\Contao\NewsBundle\ContaoNewsBundle::class)) {
            $event->setTable('tl_news')
                ->addChildrenTable('tl_content')
            ;

            $event->setTable('tl_news_archive')
                ->addChildrenTable('tl_article')
            ;

            Multilang::addRedirectHook(function ($langKey, $translatedPage) {
                global $objPage;
                $articles = \Contao\ArticleModel::findByPid($objPage->id);
                if (!$articles) return null;
                $elements = \Contao\ContentModel::findBy([
                    'pid IN (' . implode(',', $articles->fetchEach('id')) . ')',
                    'type = "module"'
                ], []);
                if (!$elements) return null;
                $modules = \Contao\ModuleModel::findBy([
                    'id IN (' . implode(',', $elements->fetchEach('module')) . ')',
                    'type = "newsreader"'
                ], []);
                if (!$modules) return null;
                $news = \Contao\NewsModel::findOneByAlias(basename($_SERVER['REQUEST_URI']));
                $translatedNews = EntityMultilang::getLangVariant($news, $langKey);
                return $translatedPage->getFrontendUrl() . "/" . $translatedNews->alias;
            });
        }
    }
}
