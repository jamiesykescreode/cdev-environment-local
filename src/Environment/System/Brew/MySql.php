<?php

namespace Cdev\Local\Environment\System\Brew;

use Cdev\Local\Environment\System\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Finder\Finder;
use Creode\Cdev\Config;

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
            throw new \Exception('Cannot find ' . $this::COMMAND . ' command! Please install using required method.');
        }
        
        // Check if the database for the project exists.
        $projectName = $this->_configHelper->getProjectName($config);
        $databaseExists = $this->databaseExists($projectName);

        // If it doesn't then create it and import from the dbs folder.
        if (!$databaseExists) {
            $this->createDatabase($path, $projectName);

            // Trigger database imports.
            $this->importDatabase($path, $projectName, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start($path, $config) {
        $this->initialise($path, $config);
        $this->runExternalCommand('brew', ['services', 'start', $this::BREW_COMMAND], $path);
    }

    /**
     * {@inheritdoc}
     */
    public function stop($path, $config) {
        $this->notSupported();
    }

    /**
     * {@inheritdoc}
     */
    public function nuke($path, Config $config) {
        $projectName = $this->_configHelper->getProjectName($config);
        $this->runExternalCommand('mysql -u root -p -e "DROP DATABASE IF EXISTS ' . $projectName . '"', [], $path);
    }

    /**
     * Connects to the projects database via terminal.
     *
     * @param string $path
     *    Path to check.
     * @param string $projectName
     *    Name of project/database to connect to.
     */
    public function connectToDb($path, $config) {
        $projectName = $this->_configHelper->getProjectName($config);
        $this->runExternalCommand('mysql -u root -p ' . $projectName, [], $path);
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
        $p = new Process('mysqlshow | grep -w "$DB_NAME"');
        $p->run(null, ['$DB_NAME' => $dbName]);

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
     * Runs command to create the database.
     *
     * @param string $path
     *    Path to current directory.
     * @param string $dbName
     *    Name of database to create.
     */
    private function createDatabase($path, $dbName) {
        $this->runExternalCommand('mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ' . $dbName . '"', [], $path);
    }

    /**
     * Imports database.
     * 
     * @param string $path
     *    Path to current directory.
     * @param string $projectName
     *    Name of the project/database to use on import.
     */
    private function importDatabase($path, $projectName, $config) {
        if (!$files = $this->loadSqlFiles($path, $config)) {
            return false;
        }

        // Runs through and imports them.
        foreach ($files as $filePath) {
            $this->importDatabaseFile($filePath, $projectName);
        }
    }

    /**
     * Loads in all sql files required in alphabetical order.
     *
     * @param string $path
     *    Path to current directory.
     * @param Creode\Cdev\Config $config
     *    Cdev Config file.
     * @return string[]
     *    List of paths to sql files.
     */
    private function loadSqlFiles($path, Config $config) {
        // Load all the files from the db folder.
        $finder = new Finder();
        $file_paths = [];

        $db_dir = 'db';
        if ($config->get('storage') && isset($config->get('storage')['db-dir'])) {
            $db_dir = $config->get('storage')['db-dir'];
        }

        if (!is_dir($path . '/' . $db_dir)) {
            return $file_paths;
        }

        $finder->files()->in($path . '/' . $db_dir)->name('/\.sql$/');

        if (!$finder->hasResults()) {
            return $file_paths;
        }
        
        foreach ($finder as $file) {
            $file_paths[] = $file->getRealPath();
        }

        // Sort them alphabetically.
        sort($file_paths);

        return $file_paths;
    }

    /**
     * Runs command to install a single file.
     *
     * @param string $file_path
     *    Absolute path to file.
     * @param string $projectName
     *    Name of project/database to import to.
     */
    private function importDatabaseFile($filePath, $projectName) {
        // Check if PV is installed.
        $process = new Process(['which', 'pv']);
        $process->run();

        $pv_support = false;
        if ($process->isSuccessful()) {
            $pv_support = true;
        }

        // Slugify the project name, set max length to 64 and trim any extra space.
        $valid_project_name = trim(substr($this->slugify($projectName), 0, 64));

        // If pv not installed then output a nice message and run a regular import.
        $main_command = "pv $filePath | mysql -u root -p " . $projectName;
        if (!$pv_support) {
            echo ">>> PV support has not been found. In order to get progress reports of import process please install it using `brew install pv`\n";
            $main_command = 'mysql -u root -p ' . $projectName . ' < ' . $filePath;
        }

        // Write sql command to import.
        $this->runExternalCommand($main_command, [], getcwd());
    }

    private function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}