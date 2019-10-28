<?php

namespace Cdev\Local\Environment\System\Brew;

use Cdev\Local\Environment\System\Helpers\ApacheHelper;
use Creode\System\Command;
use Config as PearConfig;
use Cdev\Local\Environment\System\Config\ConfigHelper;
use Pear;

class Apache extends Command {
    const COMMAND = 'apachectl';

    /**
     * Apache Helper.
     *
     * @var \Cdev\Local\Environment\System\Helpers\ApacheHelper
     */
    private $_apache;

    /**
     * Config Helper.
     *
     * @var \Cdev\Local\Environment\System\Config\ConfigHelper
     */
    private $_configHelper;

    /**
     * Constructor.
     *
     * @param ApacheHelper $apache
     * @param ConfigHelper $configHelper
     */
    public function __construct($apache, $configHelper) {
        $this->_apache = $apache;
        $this->_configHelper = $configHelper;
    }

    /**
     * Initialises the Apache Setup (create hosts).
     * @return void
     */
    private function initialise($config) {
        if (!$this->_apache->meetsDependencies()) {
            echo "Ensure the following Apache modules are installed and loaded:\n " . implode("\n ", ApacheHelper::MODULE_DEPENDENCIES);
        }

        $hostname = $this->_configHelper->getHostname($config);
        $path = $this->_configHelper->getSitePath($config);

        // Check if host exists.
        if (!$this->_apache->siteConfigExists($hostname, $path)) {
            echo 'No configuration!';
            $this->_apache->addHost($hostname, $path, $config);
        }

        // echo $this->_apache->siteConfigExists($hostname, $path);

    }

    /**
     * Starts up an Apache Server.
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     * @return void
     */
    public function start($path, $config) {
        $this->initialise($config);
        // $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'start'], $path);
    }

    /**
     * Stops an Apache Server.
     *
     * @param string $path
     * @return void
     */
    public function stop($path)
    {
        $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'stop'], $path);
    }
}