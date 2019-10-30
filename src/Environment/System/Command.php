<?php

namespace Cdev\Local\Environment\System;

use Creode\System\Command as CreodeCommand;
use Creode\Cdev\Config;

/**
 * Ensures start, stop and nuke commands are used for new services.
 */
abstract class Command extends CreodeCommand {
    /**
     * Starts a service.
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     */
    abstract public function start($path, Config $config);

    /**
     * Stops a service.
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     */
    abstract public function stop($path, Config $config);

    /**
     * Nukes a services configuration.
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     */
    abstract public function nuke($path, Config $config);

    /**
     * Triggers error stating the function is not supported.
     */
    public function notSupported() {
        throw new \Exception('This command is currently not supported in this environment.');
    }
}