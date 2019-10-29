<?php

namespace Cdev\Local\Environment;

use Creode\Cdev\Config;
use Creode\Environment\Environment;
use Creode\Framework\Framework;
use Symfony\Component\Console\Input\InputInterface;
use Creode\System\Command;

class Local extends Environment
{
    const NAME = 'local';
    const LABEL = 'Local';
    const COMMAND_NAMESPACE = 'brew';

    /**
     * @var Framework
     */
    protected $_framework;

    /**
     * @var Creode\Cdev\Config
     */
    private $_config;

    /**
     * @var \Cdev\Local\Environment\System\Brew\Apache
     */
    private $_apache;

    /**
     * @var \Cdev\Local\Environment\System\Brew\MySql
     */
    private $_mysql;

    /**
     * @param Framework $framework
     * @param Config $config
     * @return null
     */
    public function __construct(
        Framework $framework,
        Config $config,
        Command $apache,
        Command $mysql
    ) {
        $this->_framework = $framework;
        $this->_config = $config;
        $this->_apache = $apache;
        $this->_mysql = $mysql;
    }

    /**
     * Sets the inputs
     * @param InputInterface $input 
     * @return type
     */
    public function input(InputInterface $input)
    {
        $this->_input = $input;
    }

    /**
     * Functionality that runs on env:start.
     */
    public function start()
    {
        $this->logTitle('Starting dev environment...');

        $path = $this->_input->getOption('path');
        $this->_apache->start($path, $this->_config);
        $this->_mysql->start($path, $this->_config);
    }

    /**
     * Functionality that runs on env:stop.
     */
    public function stop()
    {
        $this->logTitle('Stopping dev environment...');

        // Start out by removing the configuration from the apache hosts file.
        $path = $this->_input->getOption('path');
        $this->_apache->stop($this->_config);
    }

    /**
     * Functionality associated with env:nuke.
     */
    public function nuke()
    {
        $this->logTitle('Nuking dev environment...');
        
        $path = $this->_input->getOption('path');
        $this->_apache->nuke($this->_config);
    }

    public function status()
    {
        $this->displayInstallationMessage();
    }

    public function cleanup()
    {
        // TODO: Maybe do a removal of the hosts config here.
        $this->displayInstallationMessage();
    }

    public function ssh()
    {
        $this->displayInstallationMessage();
    }

    public function dbConnect()
    {
        $this->logTitle('Connecting to database...');
        $this->displayInstallationMessage();
    }

    public function runCommand(array $command = array(), $elevatePermissions = false)
    {
        $this->displayInstallationMessage();
    }

    public function displayInstallationMessage()
    {
        throw new \Exception('This command is currently not supported in this environment.');
    }
}
