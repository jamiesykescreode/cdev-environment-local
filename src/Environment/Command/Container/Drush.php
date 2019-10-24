<?php
namespace Cdev\Local\Environment\Command\Container;

class Drush extends Container
{
    const COMMAND_NAME = 'container:drush:configure';
    const COMMAND_DESC = 'Configures the Drush container';
    const CONFIG_FILE = 'drush.yml';
    const CONFIG_NODE = 'drush';

    protected $_config = 
    [
        'active' => false,
        'container_name' => 'project_drush',
        'image' => 'drupallocal/drush',
        'links' => [
            'mysql'
        ],
        'volumes_from' => [
            'php'
        ]
    ];

    protected function askQuestions()
    {
        $path = $this->_input->getOption('path');
        $src = $this->_input->getOption('src');
        $localname = $this->_input->getOption('name');
        $localport = $this->_input->getOption('port');

        $this->_config['container_name'] = $localname . '_drush';
    }
}
