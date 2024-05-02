<?php

namespace Aznoqmous\ContaoMultilangBundle\Multilang;

use Contao\Config;
use Contao\Controller;

/**
 * Utility class handling dca file translation
 */
final class DcaFileMultilang extends DcaMultilang
{

    private static $defaultConfigKey = "multilang_default";
    private static $langConfigPrefix = "multilang_";

    public static function set($table)
    {
        $instance = new self($table);
        if (!Multilang::hasLanguages()) return $instance;

        $instance->addSubmitCallback(function () use ($table) {
            self::setLangConfig($GLOBALS['TL_CONFIG'], Multilang::getActiveLanguageKey());
        });

        $instance->addLoadCallback(function () {
            $currentLang = Config::get('multilang_current_lang');
            self::persistConfig();
            if ($currentLang && $currentLang == Multilang::getActiveLanguageKey()) return;
            Config::persist('multilang_current_lang', Multilang::getActiveLanguageKey());
            Controller::reload();
        });

//        // TODO: Add following DcaFile buttons
//        $GLOBALS['TL_DCA'][$table]['edit']['buttons_callback'][] = function($arrButtons){
//          $arrButtons['setAsDefault'] = "<button type='submit' name='setAsDefault' class='tl_submit'>" . $GLOBALS['TL_LANG']['multilang']['setAsDefault'] . "</button>";
//          return $arrButtons;
//        };
//        $GLOBALS['TL_DCA'][$table]['edit']['buttons_callback'][] = function($arrButtons){
//          $arrButtons['override'] = "<button type='submit' name='override' class='tl_submit'>" . $GLOBALS['TL_LANG']['multilang']['override'] . "</button>";
//          return $arrButtons;
//        };

        return $instance;
    }

    /**
     * Load config for frontend usage
     * @return void
     */
    public static function loadConfig()
    {
        if (!Multilang::hasLanguages()) return;
        $langConfig = self::getLangConfig(Multilang::getActiveLanguageKey());
        foreach($langConfig as $key => $value){
            if(!is_string($value)) continue;
            Config::set($key, $value);
        }
    }

    /**
     * Persist config for backend modifications
     * @return void
     */
    private static function persistConfig()
    {
        if (!Multilang::hasLanguages()) return;
        $langConfig = self::getLangConfig(Multilang::getActiveLanguageKey());

        foreach($langConfig as $key => $value){
            if(is_object($value) ||is_array($value)) continue;
            Config::persist($key, $value);
        }
    }

    private static function setLangConfig($params, $langKey)
    {
        $params = self::filterKeys($params);
        $defaultConfig = self::getDefaultConfig();

        /* Add missing parameters to defaultConfig */
        $missingKeys = array_diff(array_keys($params), array_keys($defaultConfig));
        if (count($missingKeys)) {
            $missingParams = [];
            foreach ($missingKeys as $key) {
                $missingParams[$key] = $params[$key];
            }
            self::setDefaultConfig(array_merge($defaultConfig, $missingParams));

            $defaultConfig = self::getDefaultConfig();
        }

        /* Get distinct config */
        $distinctConfig = array_diff_assoc($params, self::getLangConfig($langKey));
        self::addToLangSpecificConfig($distinctConfig, $langKey);


        /* Apply common config to default and remove it from lang specific configs */
        // Get common config
        $arrCommon = [];
        foreach(Multilang::getEnabledLanguages() as $language){
            $langConfig = self::getLangSpecificConfig($language->key);
            if(!$arrCommon) $arrCommon = $langConfig;
            else $arrCommon = array_intersect_assoc($arrCommon, $langConfig);
        }


        // Set common config to default config
        self::setDefaultConfig(array_merge($defaultConfig, $arrCommon));

        // Apply lang specific configs
        foreach(Multilang::getEnabledLanguages() as $language){
            $langConfig = self::getLangSpecificConfig($language->key);
            self::setLangSpecificConfig(array_diff_assoc($langConfig, $arrCommon), $language->key);
        }

    }

    private static function setDefaultConfig($params)
    {
        $params = self::filterKeys($params);
        Config::set(self::$defaultConfigKey, json_encode($params));
        Config::persist(self::$defaultConfigKey, json_encode($params));
    }

    public static function getDefaultConfig()
    {
        if (!$GLOBALS['TL_CONFIG']) return null;
        if (!$GLOBALS['TL_CONFIG'][self::$defaultConfigKey]) {
            self::setDefaultConfig($GLOBALS['TL_CONFIG']);
        }
        return json_decode($GLOBALS['TL_CONFIG'][self::$defaultConfigKey], true);
    }

    private static function setLangSpecificConfig($params, $langKey){
        $params = self::filterKeys($params);
        $defaultConfig = self::getDefaultConfig();
        $params = array_filter($params, function($value,$key)use($defaultConfig){
            return $defaultConfig[$key] != $value;
        }, ARRAY_FILTER_USE_BOTH);
        Config::set(self::$langConfigPrefix . $langKey, json_encode($params));
        Config::persist(self::$langConfigPrefix . $langKey, json_encode($params));
    }

    private static function addToLangSpecificConfig($params, $langKey){
        $config = self::getLangSpecificConfig($langKey);
        self::setLangSpecificConfig(array_merge($config, $params), $langKey);
    }

    public static function getLangSpecificConfig($langKey){
        $langConfig = Config::get(self::$langConfigPrefix . $langKey);
        return $langConfig ? json_decode($langConfig, true) : [];
    }

    private static function getLangConfig($langKey)
    {
        $defaultConfig = self::getDefaultConfig();
        $langConfig = self::getLangSpecificConfig($langKey);
        return array_merge($defaultConfig, $langConfig);
    }

    private static function filterKeys($params)
    {
        return array_filter($params, function ($key) {
            return !preg_match("/^" . self::$langConfigPrefix . "/", $key);
        }, ARRAY_FILTER_USE_KEY);
    }
}
