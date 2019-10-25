<?php

namespace Cdev\Local\Environment\System\Config;

use Creode\Cdev\Config;

class ConfigHelper {
    const HOST_SUFFIX = '.dev.com';

    /**
     * Cdev Configuration.
     *
     * @var Creode\Cdev\Config
     */
    private $_config; 
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }

    public static function getHostname(Config $config) 
    {
        return $config->get('local')['name'] . self::HOST_SUFFIX;
    }

    public static function getSitePath(Config $config) {
        return getcwd() . '/' . $config->get('dir')['src'];
    }
}