<?php

namespace Cdev\Local\Environment\Command;

use Creode\Cdev\Command\ConfigurationCommand;
use Creode\Cdev\Config;
use Cdev\Local\Environment\Local;
use Creode\System\Composer\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SetupEnvCommand extends ConfigurationCommand
{
    protected $_config = [
        'config' => [
            'local' => [
                'name' => null,
                'package' => null,
                'php-version' => null,
                'apache-subpath' => null,
            ]
        ]
    ];

    protected $_previousConfig;

    /**
     * @var boolean
     */
    private $_usingLocalBuilds = false;

    /**
     * @var Local
     */
    protected $_local;

    /**
     * @var Composer
     */
    protected $_composer;

    /**
     * @var Filesystem
     */
    protected $_fs;

    /**
     * Constructor
     * @param Local $local
     * @param Composer $composer
     * @return null
     */
    public function __construct(
        Local $local,
        Composer $composer,
        Filesystem $fs
    ) {
        $this->_local = $local;
        $this->_composer = $composer;
        $this->_fs = $fs;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('brew:setup');
        $this->setHidden(true);
        $this->setDescription('Sets up the local environment config');

        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'Path to run commands on. Defaults to the directory the command is run from',
            getcwd()
        );

        $this->addOption(
            'composer',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Path to composer executable',
            '/usr/local/bin/composer.phar'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $path = $this->_input->getOption('path');

        $this->loadConfig($path . '/' . Config::CONFIG_DIR, Config::CONFIG_FILE, $output);

        $this->_previousConfig = $this->_config;

        $this->askQuestions();

        $this->saveConfig($path . '/' . Config::CONFIG_DIR, Config::CONFIG_FILE);
    }

    private function askQuestions()
    {
        $helper = $this->getHelper('question');

        $default = $this->_config['config']['local']['name'];
        $question = new Question(
            '<question>Project name/domain (xxxx).dev.com</question> : [Current: <info>' . (isset($default) ? $default : 'None') . '</info>]',
            $default
        );
        $question->setValidator(function ($answer) {
            if (!filter_var('http://' . $answer, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException(
                    'Local project name must be suitable for use in domain name (no spaces, underscores etc.)'
                );
            }

            return $answer;
        });
        $this->_config['config']['local']['name'] = $helper->ask($this->_input, $this->_output, $question);

        $default = $this->_config['config']['local']['package'];
        $question = new Question(
            '<question>Composer package name (<vendor>/<name>)</question> : [Current: <info>' . (isset($default) ? $default : 'None') . '</info>]',
            $default
        );
        $question->setValidator(function ($answer) {
            if (!preg_match('/^[A-Za-z0-9]+\/[-A-Za-z0-9]+$/', $answer)) {
                throw new \RuntimeException(
                    'Package name must be in the format <vendor>/<name> e.g. creode/magento-1'
                );
            }

            return $answer;
        });
        $this->_config['config']['local']['package'] = $helper->ask($this->_input, $this->_output, $question);

        $phpVersions = $this->getAvailablePHPVersions();

        $default = $this->_config['config']['local']['php-version'];

        $keys = array_keys($phpVersions);
        $fallback = end($keys);
        $question = new ChoiceQuestion(
            '<question>Please select version of PHP for this site.</question> : [Current: <info>' . (isset($default) ? $default : $fallback) . '</info>]',
            $phpVersions,
            $default
        );

        $this->_config['config']['local']['php-version'] = $helper->ask($this->_input, $this->_output, $question);

        $default = $this->_config['config']['local']['apache-subpath'];
        $question = new Question(
            '<question>Subfolder for Apache i.e. "web"</question> : [Current: <info>' . (isset($default) ? $default : '') . '</info>]',
            $default
        );

        $question->setValidator(function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return null;
            }
            
            return $value;
        });

        $this->_config['config']['local']['apache-subpath'] = $helper->ask($this->_input, $this->_output, $question);

        if ($this->_usingLocalBuilds) {
            $this->composerInit();
        }
    }

    /**
     * Initialises composer and installs creode local tools
     * @return type
     */
    private function composerInit()
    {
        $this->_composer->setPath(
            $this->_input->getOption('composer')
        );

        $path = $this->_input->getOption('path');

        // init
        $this->_output->writeln('<info>Initialising composer</info>');

        if ($this->_fs->exists($path . '/composer.json')) {
            $this->_output->writeln('<comment>composer.json already exists, skipping</comment>');
            return;
        }

        $this->_composer->init(
            $path,
            $this->_config['config']['local']['package'],
            [
                '--require-dev', 'creode/local:~1.0.0',
                '--repository', '{"type": "vcs", "url": "git@codebasehq.com:creode/creode/local.git"}'
            ]
        );

        // install
        $this->_output->writeln('<info>Running composer install</info>');

        $this->_composer->install($path);
    }

    private function getAvailablePHPVersions() {
        $process = new Process('brew services list | grep php@');
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $listedVersions = explode("\n", $process->getOutput());

        $filteredList = array_filter(
            array_map(function($version) {
                return substr($version, 0, strpos($version, ' '));
            }, $listedVersions)
        );

        foreach($filteredList as $key => $value) {
            unset($filteredList[$key]);
            $filteredList[str_replace('php@', '', $value)] = $value;
        }

        return $filteredList;
    }
}
