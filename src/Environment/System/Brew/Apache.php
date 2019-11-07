<?php

namespace Cdev\Local\Environment\System\Brew;

use Cdev\Local\Environment\System\Command;
use Cdev\Local\Environment\System\Helpers\ApacheHelper;
use Cdev\Local\Environment\System\Config\ConfigHelper;
use Creode\Cdev\Config;

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
     */
    private function initialise($path, $config) {
        if (!$this->_apache->meetsDependencies()) {
            throw new \Exception("Ensure the following Apache modules are installed and loaded:\n " . implode("\n ", ApacheHelper::MODULE_DEPENDENCIES));
        }

        $hostname = $this->_configHelper->getHostname($config);
        $path = $this->_configHelper->getSitePath($path, $config);

        // Check if host exists.
        if (!$this->_apache->siteConfigExists($hostname)) {
            echo '>>> Adding a new configuration for `'. $hostname . '` inside `' . $this->_apache->configPath . '`.';
            $this->_apache->addHost($hostname, $path, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start($path, $config) {
        $this->initialise($path, $config);

        $this->triggerConfigurationUpdate($path, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function stop($path, Config $config)
    {
        $hostname = $this->_configHelper->getHostname($config);

        echo '>>> Removing host configuration for `' . $hostname . "`\n";
        $this->_apache->removeHost($this->_configHelper->getHostname($config));
    }

    /**
     * {@inheritdoc}
     */
    public function nuke($path, Config $config) {
        $this->stop($path, $config);
    }

    /**
     * Applies configuration change by either starting or restarting.
     * Because there is no current way of checking if apache is running via
     * commandline I am stopping it and starting it. These may result in an error
     * if running but this can be disregarded.
     *
     * @param string $path
     *    Path to project.
     * @param Creode\Cdev\Config $config
     *    Cdev Configuration object.
     */
    private function triggerConfigurationUpdate($path, Config $config) {
        // Restarts since apache may have already been started at this point.
        $hostname = $this->_configHelper->getHostname($config);
        echo '>>> Ensuring that the configuration has been applied for `' . $hostname . "`\n";

        $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'stop'], $path);
        $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'start'], $path);
    }
}