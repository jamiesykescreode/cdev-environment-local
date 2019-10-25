<?php

namespace Cdev\Local\Environment\System\Brew;

use Creode\System\Command;
use Config;
use Pear;

class Apache extends Command {
    const COMMAND = 'apachectl';

    /**
     * Undocumented function
     *
     * @return void
     */
    private function initialise() {
        // TODO: Not sure here if to do something where I setup the 
        $config = new Config();

        $file = '/usr/local/etc/httpd/extra/httpd-vhosts.conf';
        $root = $config->parseConfig($file, 'apache');

        var_dump($root);

        if (PEAR::isError($root)) {
            echo 'Error reading config: ' . $root->getMessage() . "\n";
            exit(1);
        }

        var_dump('it read it!');
    }

    /**
     * Starts up an Apache Server.
     *
     * @param string $path
     * @return void
     */
    public function start($path) {
        $this->initialise();
        $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'start'], $path);
    }

    /**
     * Stops an Apache Server.
     *
     * @param string $path
     * @return void
     */
    public function stop($path)
    {
        $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'stop'], $path);
    }
}