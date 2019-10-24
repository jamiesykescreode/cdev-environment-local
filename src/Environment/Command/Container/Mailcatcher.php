<?php
namespace Cdev\Local\Environment\Command\Container;

class Mailcatcher extends Container
{
    const COMMAND_NAME = 'container:mailcatcher:configure';
    const COMMAND_DESC = 'Configures the Mailcatcher container';
    const CONFIG_FILE = 'mailcatcher.yml';
    const CONFIG_NODE = 'mailcatcher';

    protected $_config = 
    [
        'active' => true,
        'image' => 'schickling/mailcatcher',
        'container_name' => 'project_mailcatcher',
        'ports' => [
            '1080:1080'
        ]
    ];

    protected function askQuestions()
    {
        $path = $this->_input->getOption('path');
        $localname = $this->_input->getOption('name');
        $localport = $this->_input->getOption('port');

        $this->_config['container_name'] = $localname . '_mailcatcher';

        $this->_config['ports'] = ['5' . $localport . ':1080'];
    }
}
