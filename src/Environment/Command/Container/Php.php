<?php
namespace Cdev\Local\Environment\Command\Container;

class Php extends Container
{
    const COMMAND_NAME = 'container:php:configure';
    const COMMAND_DESC = 'Configures the PHP container';
    const CONFIG_FILE = 'php.yml';
    const CONFIG_NODE = 'php';

    protected $_config = 
    [
        'active' => true,
        'container_name' => 'project_php',
        'ports' => [
            '80:80'
        ],
        'environment' => [
            'VIRTUAL_HOST' => '.project.local'
        ],
        'volumes' => [
            ['../src:/var/www/html']
        ]
    ];

    private $_syncConfig = [
        'sync' => [
            'name' => 'project-website-code-sync',
            'default' => [
                'src' => '../src',
                'sync_userid' => 1000, # www-data
                'sync_strategy' => 'unison',
                'sync_excludes' => [
                    '.sass-cache',
                    'sass',
                    'sass-cache',
                    'bower.json',
                    'package.json',
                    'Gruntfile',
                    'bower_components',
                    'node_modules',
                    '.gitignore',
                    '.git',
                    '*.scss',
                    '*.sass'
                ]
            ]
        ],
        'volumes' => [
            ['syncname:/var/www/html:nocopy']
        ]
    ];

    protected function askQuestions()
    {
        $path = $this->_input->getOption('path');
        $src = $this->_input->getOption('src');
        $localname = $this->_input->getOption('name');
        $localport = $this->_input->getOption('port');
        $volumeName = $this->_input->getOption('volume');

        // TODO: What if there are multiple sites? Can we setup multiple PHP containers
        // usage example will be Drupal sites where clearing cache doesn't do all sites
        $this->buildOrImage(
            '../vendor/creode/local/images/php/7.0',
            'creode/php-apache:7.0',
            $this->_config,
            [   // builds
                '../vendor/creode/local/images/php/7.0' => 'PHP 7.0',
                '../vendor/creode/local/images/php/5.6' => 'PHP 5.6',
                '../vendor/creode/local/images/php/5.6-ioncube' => 'PHP 5.6 with ionCube',
                '../vendor/creode/local/images/php/5.3' => 'PHP 5.3'
            ],
            [   // images
                'creode/php-apache:7.0' => 'PHP 7.0',
                'creode/php-apache:5.6' => 'PHP 5.6',
                'creode/php-apache:5.6-ioncube' => 'PHP 5.6 with ionCube',
                'creode/php-apache:5.3' => 'PHP 5.3'
            ]
        );

        $this->_config['container_name'] = $localname . '_php';

        $this->_config['ports'] = ['3' . $localport . ':80'];

        $this->_config['environment']['VIRTUAL_HOST'] = '.' . $localname . '.local';

        $this->_config['links'] = []; 

        if ($volumeName) {
            $this->_config['volumes'] = [$volumeName . ':/var/www/html:nocopy'];
        } else {
            $this->_config['volumes'] = ['../' . $src . ':/var/www/html'];
        }
        
    }
}
