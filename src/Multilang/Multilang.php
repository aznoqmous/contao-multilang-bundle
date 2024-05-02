<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Aznoqmous\ContaoMultilangBundle\Config\MultilangConfig;
use Contao\Config;
use Contao\Database;
use Contao\Frontend;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Contao\ThemeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

final class Multilang
{

    private static $objInstance;

    /**
     * @var array|DcaMultilangConfiguration
     */
    private $tables = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (static::$objInstance === null) {
            static::$objInstance = new static();
        }
        return static::$objInstance;
    }

    public function addTable($strTable)
    {
        $configuration = new DcaMultilangConfiguration($strTable);
        $this->tables[$strTable] = $configuration;
        return $configuration;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getTable($strTable): DcaMultilangConfiguration
    {
        if (!$this->tables[$strTable]) throw new \Exception("$strTable is not defined. Register it using DcaTableMultilang::set(\"$strTable\") or DcaFileMultilang::set(\"$strTable\")");
        return $this->tables[$strTable];
    }

    public function isTableMultilang($strTable)
    {
        return $this->tables[$strTable];
    }


    public static function hasLanguages()
    {
        return count(self::getLanguages()) > 0;
    }

    /**
     * Return an array of Language from available language in contao settings
     * @return array|mixed
     */
    public static function getLanguages()
    {
        if (!$GLOBALS['TL_MULTILANG_LANGUAGES']) $GLOBALS['TL_MULTILANG_LANGUAGES'] = self::getEnabledLanguages();
        return $GLOBALS['TL_MULTILANG_LANGUAGES'];
    }

    public static function getLanguageKeys()
    {
        return array_map(function ($lang) {
            return $lang->key;
        }, self::getLanguages());
    }

    public static function getDefaultLanguage()
    {
        $languages = self::getLanguages();
        return $languages && count($languages) ? $languages[0] : null;
    }

    public static function getLanguageByKey($key)
    {
        $matchingLanguage = array_values(array_filter(self::getLanguages(), fn($language) => $language->key == $key));
        return count($matchingLanguage) ? $matchingLanguage[0] : null;
    }

    public static function getActiveLanguage()
    {
        if (TL_MODE == 'BE') $langKey = self::getSessionLanguage() ?: self::getDefaultLanguage()->key;
        else {
            /* Get active language once, then return stored value */
            if (!$GLOBALS['TL_MULTILANG_LANGUAGE']) {
                if (System::getContainer()->getParameter('contao.prepend_locale')) {
                    if (System::getContainer()->get('contao.security.token_checker')->isPreviewMode()) {
                        $GLOBALS['TL_MULTILANG_LANGUAGE'] = explode('/', $_SERVER['REQUEST_URI'])[2];
                    } else {
                        $GLOBALS['TL_MULTILANG_LANGUAGE'] = explode('/', $_SERVER['REQUEST_URI'])[1];
                    }
                } else {
                    $root = Frontend::getRootPageFromUrl();
                    $GLOBALS['TL_MULTILANG_LANGUAGE'] = $root->language;
                }
            }
            $langKey = $GLOBALS['TL_MULTILANG_LANGUAGE'];
        }
        return self::getLanguageByKey($langKey);
    }

    public static function setActiveLanguage($langKey)
    {
        $_SESSION['lang'] = $langKey;
    }

    public static function getSessionLanguage()
    {
        return $_SESSION['lang'];
    }

    public static function getActiveLanguageKey()
    {
        return self::getActiveLanguage()->key;
    }

    public static function getDefaultLanguageKey()
    {
        return self::getDefaultLanguage()->key;
    }

    public static function isDefaultLanguage()
    {
        return self::getActiveLanguageKey() == self::getDefaultLanguageKey();
    }

    /**
     * Returns languages available inside Contao
     * */
    public static function getSystemLanguages()
    {
        $languages = [];
        foreach (System::getContainer()->get('contao.intl.locales')->getEnabledLocales(null, true) as $key => $value) {
            $splitted = explode(' ', $key);
            $key = array_pop($splitted);
            $languages[] = new Language($key, $value);
        }
        return $languages;
    }

    /**
     * Return system languages filtered with multilang.yml settings
     */
    public static function getAvailableLanguages()
    {
        $systemLanguages = self::getSystemLanguages();
        $configLanguages = MultilangConfig::get('languages');
        if (!is_array($configLanguages)) return $systemLanguages;
        $filteredLanguages = array_filter($systemLanguages, function ($lang) use ($configLanguages) {
            return in_array($lang->key, $configLanguages);
        });
        return array_values($filteredLanguages);
    }

    /**
     * Return enabled languages in contao settings
     */
    public static function getEnabledLanguages()
    {
        $availableLanguages = self::getAvailableLanguages();
        $settingsLanguages = Config::get('languages');
        if (!$settingsLanguages) return [];

        $settingsLanguages = explode(',', $settingsLanguages);
        $settingsLanguages = is_array($settingsLanguages) ? $settingsLanguages : unserialize($settingsLanguages);

        $filteredLanguages = [];
        foreach ($settingsLanguages as $langKey) {
            foreach ($availableLanguages as $language) {
                if ($langKey != $language->key) continue;
                $filteredLanguages[] = $language;
                break;
            }
        }
        return $filteredLanguages;
    }

    #-------------------------------------------------------------------------------------------------------------------
    # TODO: move following methods
    #-------------------------------------------------------------------------------------------------------------------

    public static function generateAlias($model, $value)
    {
        $aliasExists = function (string $alias) use ($model): bool {
            return Database::getInstance()->prepare("SELECT id FROM {$model->getTable()} WHERE alias=? AND id!=?")->execute($alias, $model->id)->numRows > 0;
        };
        return System::getContainer()->get('contao.slug')->generate($value, [], $aliasExists);
    }

    public static function addRedirectHook($callback)
    {
        if (!is_array($GLOBALS['TL_HOOKS']['multilangRedirect'])) $GLOBALS['TL_HOOKS']['multilangRedirect'] = [];
        $GLOBALS['TL_HOOKS']['multilangRedirect'][] = $callback;
    }

    public static function getRedirectHooks()
    {
        return $GLOBALS['TL_HOOKS']['multilangRedirect'];
    }

    public static function getLanguageURL($langKey)
    {
        global $objPage;
        $translatedPage = EntityMultilang::getLangVariant($objPage, $langKey);
        $url = $translatedPage && $translatedPage->published ? $translatedPage->getFrontendUrl() : null;
        foreach (Multilang::getRedirectHooks() as $callback) {
            $newUrl = $callback($langKey, $translatedPage);
            if ($newUrl) $url = $newUrl;
        }
        return $url;
    }

    public static function redirect($path, $params = [])
    {
        $params = array_merge($_GET, $params);
        header('Location: ' . $path . (count($params) ? "?" . http_build_query($params) : ""));
        exit;
    }

    public static function findContentElementInCurrentPageByType($contentType)
    {
        global $objPage;
        $articles = \Contao\ArticleModel::findByPid($objPage->id);
        if (!$articles) return null;

        $elements = \Contao\ContentModel::findBy([
            'pid IN (' . implode(',', $articles->fetchEach('id')) . ')',
            "type = \"$contentType\""
        ], []);

        return $elements;
    }

    public static function findModuleInCurrentPageByType($moduleType)
    {

        $elements = self::findContentElementInCurrentPageByType("module");
        if (!$elements) return null;

        $modules = \Contao\ModuleModel::findBy([
            'id IN (' . implode(',', $elements->fetchEach('module')) . ')',
            "type = \"$moduleType\""
        ], []);

        return $modules;
    }
}
