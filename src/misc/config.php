<?php

namespace Penny;

use Penny\ConfigException;

class Config {
    private static $configuration;

    /**
     * Adds a custom variable to the configuration data
     *
     * @param string $key
     * @param any $value
     */
    public static function add($key, $value) {
        static::$configuration[$key] = $value;
    }

    /**
     * Loads the configuration file into storage.
     *
     * @param  string $file
     * @return bool
     */
    public static function load($file = '') {
        if (!file_exists($file)) {
            throw new ConfigException('The configuration file doesn\'t exist.');
        } else {
            $config = file_get_contents($file);
            if (!isJSON($config)) {
                throw new ConfigException('Invaild configuration format.');
            } else {
                static::$configuration = json_decode($config, true);
                return true;
            }
        }
    }

    /**
     * Tells us if the config had any data or not
     *
     * @return bool
     */
    public static function loaded() {
        return static::$configuration !== null;
    }

    /**
     * Returns the configuration information loaded from the file.
     *
     * @param string $site
     * @return array
     */
    public static function forSite($site = '') {
        if (!static::$configuration) {
            throw new ConfigException('Configuration not loaded.');
        } elseif ($site == '') {
            throw new ConfigException('Site not defined to get configuration for.');
        } elseif (!isset(static::$configuration[$site])) {
            throw new ConfigException('Site configuration doesn\'t exist.');
        } else return static::$configuration[$site];
    }

    /**
     * Gets a specific value from the config
     *
     * @param  string $key
     * @return any
     */
    public static function get($key = '', $ignore = false) {
        if (!static::$configuration) {
            throw new ConfigException('Configuration not loaded.');
        } elseif (!isset(static::$configuration[$key]) || $key == '') {
            if (!$ignore) {
                throw new ConfigException('\''.$key.'\' doesn\'t exist in the config.');
            } else return null;
        } else return static::$configuration[$key];
    }

    /**
     * Checks to see if a specific key exists in the config
     *
     * @param  string  $key
     * @return boolean
     */
    public static function hasValue($key = '') {
        if (!static::$configuration) {
            throw new ConfigException('Configuration not loaded.');
        } else return array_key_exists($key, static::$configuration);
    }

    /**
     * Returns the path to the site root folder (where all the sites are)
     *
     * @param  string $site
     * @return string
     */
    public static function siteFolder($site = '') {
        if (!static::$configuration) {
            throw new ConfigException("Configuration not loaded.");
        } elseif ($site == "") {
            throw new ConfigException("Site folder not provided.");
        } elseif (!isset(static::$configuration['siteRootFolder'])) {
            return "sites/".$site;
        } else {
            return static::$configuration['siteRootFolder']."/".$site;
        }
    }

    /**
     * Returns the API folder
     *
     * @return string
     */
    public static function apiFolder() {
        if (!static::$configuration) {
            throw new ConfigException("Configuration not loaded.");
        } elseif (!isset(static::$configuration['apiRootFolder'])) {
            return "apis/";
        } else {
            return static::$configuration['apiRootFolder']."/";
        }
    }

    /**
     * Returns all of the configuration data
     *
     * @return array
     */
    public static function getAll() {
        return static::$configuration;
    }
}
