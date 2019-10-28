<?php

namespace Cdev\Local\Environment\System\Config;

use Creode\Cdev\Config;

/**
 * Helper class to get configuration for this plugin out.
 */
class ConfigHelper {
    /**
     * @var string
     *    Which suffix should be used to append to hostnames.
     */
    const HOST_SUFFIX = '.dev.com';

    /**
     * Cdev Configuration.
     *
     * @var Creode\Cdev\Config
     */
    private $_config;

    /**
     * Constructor
     *
     * @param Creode\Cdev\Config $config
     */
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }

    /**
     * Gets the hostname for the website
     *
     * @param Creode\Cdev\Config $config
     * @return string
     *    Hostname of the website.
     */
    public static function getHostname(Config $config) 
    {
        return $config->get('local')['name'] . self::HOST_SUFFIX;
    }

    /**
     * Gets the path to the website on the harddrive.
     *
     * @param Creode\Cdev\Config $config
     * @return string
     *    Path to the website.
     */
    public static function getSitePath(Config $config) {
        return getcwd() . '/' . $config->get('dir')['src'];
    }

    /**
     * Gets the version of PHP to use for the site.
     *
     * @param Creode\Cdev\Config $config
     * @return string
     *     Version of PHP site is configured to use e.g. "7.2".
     */
    public static function getPhpVersion($config) {
        return $config->get('local')['php-version'];
    }
}