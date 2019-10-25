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
     * @var Config
     */
    private $_config;

    /**
     * @var \Cdev\Local\Environment\System\Brew\Apache
     */
    private $_apache;

    /**
     * @param Framework $framework
     * @param Config $config
     * @return null
     */
    public function __construct(
        Framework $framework,
        Config $config,
        Command $apache
    ) {
        $this->_framework = $framework;
        $this->_config = $config;
        $this->_apache = $apache;
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

    public function start()
    {
        $this->logTitle('Starting dev environment...');

        $path = $this->_input->getOption('path');
        $this->_apache->start($path);
    }

    public function stop()
    {
        $this->logTitle('Stopping dev environment...');

        $path = $this->_input->getOption('path');
        $this->_apache->stop($path);
    }

    public function nuke()
    {
        // TODO: Decide on what to do here. Do we remove the site from configuration 
        // or just state that it's not supported.
        $this->logTitle('Nuking dev environment...');
        $this->displayInstallationMessage();
    }

    public function status()
    {
        $this->logTitle('Environment status');
        $this->displayInstallationMessage();
    }

    public function cleanup()
    {
        $this->logTitle('Cleaning up Docker leftovers...');
        $this->displayInstallationMessage();
    }

    public function ssh()
    {
        $this->logTitle('Connecting to server...');
        $this->displayInstallationMessage();
    }

    public function dbConnect()
    {
        $this->logTitle('Connecting to database...');
        $this->displayInstallationMessage();
    }

    public function runCommand(array $command = array(), $elevatePermissions = false)
    {
        $this->logTitle('Running command...');
        $this->displayInstallationMessage();
    }

    public function displayInstallationMessage()
    {
        throw new \Exception('This command is currently not supported in this environment.');
    }
}
