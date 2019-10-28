<?php

namespace Cdev\Local\Environment\System\Helpers;

use Pear;
use Config as PearConfig;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ApacheHelper {

    /**
     * Parsed Apache Config File
     *
     * @var Config
     */
    public $_configuration;

    /**
     * Root node of loaded apache configuration file.
     *
     * @var [type]
     */
    public $_root_node;

    /**
     * @var string[] 
     *    List of dependencies required to run.
     */
    public const MODULE_DEPENDENCIES = [
        'proxy_module',
        'proxy_http_module',
        'proxy_fcgi_module'
    ];

    private function loadApacheConfigFile($path) {
        $pearConfig = new PearConfig();
        
        $this->_configuration = $pearConfig;
        $this->_root_node = $pearConfig->parseConfig($path, 'apache');
        
        if (PEAR::isError($this->_root_node)) {
            echo 'Error reading config: ' . $this->_root_node->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Check if the path matches in the configuration file.
     */
    private static function pathMatches($path) {
        self::loadApacheConfigFile($path);
    }

    public function siteConfigExists($hostname, $path) {
        $this->loadApacheConfigFile('/usr/local/etc/httpd/extra/httpd-vhosts.conf');

        $root = $this->_root_node;

        $i = 0;
        $exists = false;
        while ($item = $root->getItem('section', 'VirtualHost', null, null, $i++)) {
            // Find out if we need to use this.
            foreach ($item->children as $child) {
                if ($child->name == 'DocumentRoot' && $child->content === $path) {
                    $exists = true;
                }
            }
        }

        return $exists;
    }

    public function addHost($hostname, $path, $config) {
        // Get the version of PHP from Config.

        // Parse it in to a listen line to allow PHP to pick up on it.

        // Build up a node with hostname, path and config.

        // Inject it into the _root variable.

        // Write the file.
    }

    /**
     * Checks if the dependencies for the existing apache configuration work.
     *
     * @return bool
     *    If we meet the apache dependencies required for our setup to work.
     */
    public function meetsDependencies() {
        $process = new Process(['apachectl', '-t', '-D', 'DUMP_MODULES']);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $module_list = $process->getOutput();

        $modules = explode("\n", $module_list);
        unset($modules[0]);

        $modules = array_filter(
            array_map(function ($module) {
                $module = substr($module, 0, strpos($module, '('));

                $module = trim($module);

                return $module;
            }, $modules)
        );

        $hasDependencies = array_reduce(self::MODULE_DEPENDENCIES, function ($carry, $module) use ($modules) {
            return $carry && in_array($module, $modules);
        }, true);

        return $hasDependencies;
    }
}