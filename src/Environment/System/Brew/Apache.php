<?php

namespace Cdev\Local\Environment\System\Brew;

use Cdev\Local\Environment\System\Helpers\ApacheHelper;
use Creode\System\Command;
use Config as PearConfig;
use Cdev\Local\Environment\System\Config\ConfigHelper;
use Pear;

class Apache extends Command {
    const COMMAND = 'apachectl';

    /**
     * Initialises the Apache Setup (create hosts).
     * @return void
     */
    private function initialise($config) {
        if (ApacheHelper::meetsDependencies()) {
            echo "Ensure the following Apache modules are installed and loaded:\n " . implode("\n ", ApacheHelper::MODULE_DEPENDENCIES);
        }


        // var_dump($modules);

        $configHelper = new ConfigHelper($config);

        // Get hostname
        $hostname = ConfigHelper::getHostname($config);

        // // Get path
        $path = '"' . ConfigHelper::getSitePath($config) . '"';

        echo $path;

        $apacheFile = new ApacheHelper();

        // // Check if host exists.
        if (!$apacheFile->siteConfigExists($hostname, $path)) {
            echo 'No configuration!';
            $apacheFile->addHost($hostname, $path, $config);
        }




















        // $pearConfig = new PearConfig();

        
        // $root = $pearConfig->parseConfig($file, 'apache');

        // if (PEAR::isError($root)) {
        //     echo 'Error reading config: ' . $root->getMessage() . "\n";
        //     exit(1);
        // }

        // $current_folder = getcwd();
        // $folder = '"' . $current_folder . '/' . $config->get('dir')['src'] . '"';
        
        // // var_dump($apache_hosts);
        // // die;

        // $i = 0;
        // while($item = $root->getItem('section', 'VirtualHost', null, null, $i++)) {
        //     // echo $item->name . "\n";

        //     $delete = false;

        //     // Find out if we need to use this.

        //     foreach($item->children as $child) {
        //         // $child->removeItem();
        //         if ($child->name == 'DocumentRoot' && $child->content === $folder) {
        //             $delete = true;
        //         }
        //     }

        //     if ($delete) {
        //         $item->removeItem();
        //     }
        // }

        // // die;

        // $pearConfig->writeConfig(getcwd() . '/dummy-apache.conf', 'apache');

        // $current_folder = getcwd();
        // $folder = '"' . $current_folder . '/' . $config->get('dir')['src'] . '"';

        // foreach($apache_hosts as $host) {
        //     var_dump($host->children);
        // }

        // die;

        // var_dump($hosts);
        // die;
    }

    /**
     * Starts up an Apache Server.
     *
     * @param string $path
     * @param Creode\Cdev\Config $config
     * @return void
     */
    public function start($path, $config) {
        $this->initialise($config);
        // $this->runExternalCommand('sudo ' . $this::COMMAND, ['-k', 'start'], $path);
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