<?php

namespace Cdev\Local\Environment\System\Brew;

use Creode\System\Command;

class Apache extends Command {
    const COMMAND = 'apachectl';

    /**
     * Undocumented function
     *
     * @return void
     */
    private function initialise() {
        // TODO: Not sure here if to do something where I setup the 
    }

    /**
     * Starts up an Apache Server.
     *
     * @param string $path
     * @return void
     */
    public function start($path) {
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