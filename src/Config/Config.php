<?php

namespace Aznoqmous\ContaoMultilangBundle\Config;

use Contao\System;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 * Get config from YML file
 */
abstract class Config {

    protected static $configFile;

    public static function get($key=null)
    {
        $config = self::getConfig();

        if(!$key) return $config;

        $walkerKey = explode('.', $key);

        $searchedConfig = $config;
        foreach($walkerKey as $key){
            if(array_key_exists($key, $searchedConfig)) $searchedConfig = $searchedConfig[$key];
        }

        if($searchedConfig === $config) return null;

        return $searchedConfig;
    }

    /**
     * Idem self::get but throw an Exception if no value is returned
     * @param $key
     */
    public static function getRequired($key)
    {
        $value = self::get($key);
        if($value === null) throw new \Exception(static::$configFile . ".yml key `$key` is not configured. Add it inside your local `config/shop.yml` file.");
        return $value;
    }

    /**
     * Return merged config.yml + local shop.yml
     * @return array
     */
    private static function getConfig()
    {
        $defaultConfigFile = System::getContainer()->get('contao.resource_locator')->locate('../config/' . static::$configFile . '.yml', null, false)[0];
        $defaultConfig = self::parseYamlFile($defaultConfigFile);
        if(!$defaultConfig) $defaultConfig = [];

        $localConfigFile = null;
        $localConfig = [];
        $localConfigPath = "../config/" . static::$configFile; // prior to cwd which is web
        if(is_file("$localConfigPath.yml")) $localConfigFile = "$localConfigPath.yml";
        if(is_file("$localConfigPath.yaml")) $localConfigFile = "$localConfigPath.yaml";
        if($localConfigFile){
            $localConfig = self::parseYamlFile($localConfigFile);
            if(!$localConfig) $localConfig = [];
        }

        return array_merge($defaultConfig, $localConfig);
    }

    /**
     * Return merged <filename>.yml or <filename>.yaml content based on bundle config + local config
     * @param $filename
     * @return array
     */
    protected static function getConfigFromFile($filename)
    {
        $defaultConfigFile = System::getContainer()->get('contao.resource_locator')->locate("../config/$filename.yml", null, false)[0];
        $defaultConfig = self::parseYamlFile($defaultConfigFile);
        if(!$defaultConfig) $defaultConfig = [];

        $localConfigFile = null;
        $localConfig = [];
        $localConfigPath = "../config/$filename"; // prior to cwd which is web
        if(is_file("$localConfigPath.yml")) $localConfigFile = "$localConfigPath.yml";
        if(is_file("$localConfigPath.yaml")) $localConfigFile = "$localConfigPath.yaml";
        if($localConfigFile){
            $localConfig = self::parseYamlFile($localConfigFile);
            if(!$localConfig) $localConfig = [];
        }

        return array_merge($defaultConfig, $localConfig);
    }

    public static function parseYamlFile($path)
    {
        $fileContent = file_get_contents($path);
        $parsedContent = Yaml::parse($fileContent);

        return $parsedContent;
    }
}
