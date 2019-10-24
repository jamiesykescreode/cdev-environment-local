<?php

namespace Cdev\Local\Environment\Command;

use Creode\Cdev\Command\ConfigurationCommand;
use Creode\Cdev\Config;
use Cdev\Local\Environment\Local;
use Creode\System\Composer\Composer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class SetupEnvCommand extends ConfigurationCommand
{
    protected $_config = [
        'config' => [
            'local' => [
                'name' => null,
                'package' => null,
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

        if ($this->_usingLocalBuilds) {
            $this->composerInit();
        }
    }

    /**
     * Asks whether a container is required and saves results
     * @param string $label 
     * @param string|array &$config 
     * @param boolean $defaultActive
     * @return boolean
     */
    private function containerRequired($label, &$config, $defaultActive)
    {
        $helper = $this->getHelper('question');

        $required = ((is_null($config) && $defaultActive) || $config);

        $optionsLabel = $required ? 'Y/n' : 'y/N';
        $question = new ConfirmationQuestion(
            '<question>' . $label . '? ' . $optionsLabel . '</question> : [Current: <info>' . ($required ? 'Yes' : 'No') . '</info>]',
            $required,
            '/^(y|j)/i'
        );
        $config = $helper->ask($this->_input, $this->_output, $question);

        return $config;
    }

    /**
     * Asks whether to use an image or build the image from local scripts
     * @param string $defaultBuild 
     * @param string $defaultImage 
     * @param array &$config
     * @param array $builds
     * @param array $images
     */
    private function buildOrImage(
        $defaultBuild,
        $defaultImage,
        array &$config,
        array $builds = [],
        array $images = []
    ) {
        $helper = $this->getHelper('question');

        $current = isset($config['build']) ? 'build' : (isset($config['image']) ? 'image' : null);
        $default = isset($current) ? $current : 'image';

        $question = new ChoiceQuestion(
            '<question>Build or Image:</question> [Current: <info>' . $default . '</info>]',
            [
                'build' => 'build',
                'image' => 'image'
            ],
            $default
        );
        $question->setErrorMessage('Choice %s is invalid.');
        $chosen = $helper->ask($this->_input, $this->_output, $question);

        switch ($chosen) {
            case 'build':
                $this->_usingLocalBuilds = true;

                if (isset($config['image'])) {
                    unset($config['image']);
                }

                $default = isset($config['build']) ? $config['build'] : $defaultBuild;

                $question = new ChoiceQuestion(
                    '<question>Build:</question> [Current: <info>' . $default . '</info>]',
                    $builds,
                    $default
                );
                $question->setErrorMessage('Build %s is invalid.');
                $config['build'] = $helper->ask($this->_input, $this->_output, $question);
                break;
            case 'image':
                if (isset($config['build'])) {
                    unset($config['build']);
                }

                $default = isset($config['image']) ? $config['image'] : $defaultImage;

                $question = new ChoiceQuestion(
                    '<question>Image:</question> [Current: <info>' . $default . '</info>]',
                    $images,
                    $default
                );
                $question->setErrorMessage('Image %s is invalid.');
                $config['image'] = $helper->ask($this->_input, $this->_output, $question);
                break;
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
}
