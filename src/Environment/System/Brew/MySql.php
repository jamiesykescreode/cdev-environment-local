<?php

namespace Cdev\Local\Environment\System\Brew;

use Creode\System\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class for handling mysql cli communication.
 */
class MySql extends Command {
    const COMMAND = 'mysql';
    const BREW_COMMAND = 'mariadb';

    /**
     * @var \Cdev\Local\Environment\System\Config\ConfigHelper
     */
    private $_configHelper;

    /**
     * Constructor for MySql.
     * 
     * @param \Cdev\Local\Environment\System\Config\ConfigHelper
     */
    public function __construct($configHelper) {
        $this->_configHelper = $configHelper;
    }

    /**
     * Initialises the MySql Setup (create hosts).
     */
    private function initialise($path, $config) {
        // Check mysql is installed.
        $installed = $this->mysqlIsInstalled();

        if (!$installed) {
            // TODO: at some point I'd like to trigger an installation command.
            throw new \Exception('Cannot find ' . $this::COMMAND . ' command! Please install using required method.');
        }
        
        // Check if the database for the project exists.
        $projectName = $this->_configHelper->getProjectName($config);
        $databaseExists = $this->databaseExists($projectName);

        // TODO: If it doesn't then create it and import from the dbs folder.
        if (!$databaseExists) {
            $this->createDatabase($path, $projectName);
        }

    }

    /**
     * Starts mysql services
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     */
    public function start($path, $config) {
        $this->initialise($path, $config);
        $this->runExternalCommand('brew', ['services', 'start', $this::BREW_COMMAND], $path);
    }

    /**
     * Checks if mysql is currently installed.
     *
     * @return string
     *    Output of installed command.
     */
    private function mysqlIsInstalled() {
        $process = new Process(['which', $this::COMMAND]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Runs a command to check if a database exists.
     *
     * @param Creode\Cdev\Config $config
     * @return bool
     *    If database exists
     */
    private function databaseExists($dbName) {
        // Find if database exists.
        $p = new Process('mysqlshow | grep -w "fitch"');
        $p->run();

        if (!$p->isSuccessful()) {
            throw new ProcessFailedException($p);
        }
        
        $exists = trim($p->getOutput());

        $databaseExists = false;
        if ($exists && strpos($exists, $dbName)) {
            $databaseExists = true;
        }

        return $databaseExists;
    }

    /**
     * Undocumented function
     *
     * @param string $path
     *    Path to current directory.
     * @param string $dbName
     *    Name of database to create.
     */
    private function createDatabase($path, $dbName) {
        $this->runExternalCommand('mysql -u root -p -e "CREATE DATABASE ' . $dbName . '"', [], $path);
    }
}