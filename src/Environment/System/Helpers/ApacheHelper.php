<?php

namespace Cdev\Local\Environment\System\Helpers;

use Pear;
use Config as PearConfig;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Cdev\Local\Environment\System\Config\ConfigHelper;

/**
 * Contains functions to help with apache setup.
 */
class ApacheHelper {

    /**
     * Path to vhosts file.
     *
     * @var string
     */
    public $configPath = '/usr/local/etc/httpd/extra/httpd-vhosts.conf';

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
     * List of dependencies required to run.
     * @var string[]
     */
    public const MODULE_DEPENDENCIES = [
        'proxy_module',
        'proxy_http_module',
        'proxy_fcgi_module'
    ];

    /**
     * Loads in an apache configuration file so that it can be used in the class.
     * 
     * @param string $path
     *    Path to the configuration file in Apache.
     */
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
     * Checks if the site configuration already exists inside the
     * apache hosts configuration.
     *
     * @param string $path
     *    Path to site.
     * @return bool
     *    Returns true if configuration site exists for site.
     */
    public function siteConfigExists($path) {
        $this->loadApacheConfigFile($this->configPath);

        // Converts file path into doc path.
        $filePath = '"' . $path . '"';

        $root = $this->_root_node;

        $i = 0;
        $exists = false;
        while ($item = $root->getItem('section', 'VirtualHost', null, null, $i++)) {
            // Find out if we need to use this.
            // TODO: At some point I may want to check the hostname instead but this is fine for now.
            foreach ($item->children as $child) {
                if ($child->name == 'DocumentRoot' && $child->content === $filePath) {
                    $exists = true;
                }
            }
        }

        return $exists;
    }

    /**
     * Handles adding the host into the apache configuration.
     *
     * @param string $hostname
     *    Hostname to use when adding to apache configuration file.
     * @param string $path
     *    Path to site.
     * @param \Cdev\Local\Environment\System\Config\ConfigHelper $config
     *    Configuration helper class to get various configuration options from.
     */
    public function addHost($hostname, $path, $config) {
        // Get the version of PHP from Config.
        $phpVersion = ConfigHelper::getPhpVersion($config);

        // Parse it in to a listen line to allow PHP to pick up on it.
        $listenLine = $this->parseListenLine($phpVersion, $path);

        // Build up a node with hostname, path and config.
        $this->_root_node->createBlank();

        // Inject it into the _root variable.
        $this->_root_node->createComment('Configuration for ' . $hostname);
        $vhost = $this->_root_node->createSection('VirtualHost', array('*:80'));

        $vhost->createDirective('DocumentRoot',  '"' . $path . '"');
        $vhost->createDirective('ServerName', $hostname);
        $vhost->createDirective('ProxyPassMatch', $listenLine);

        // Write the file.
        $this->_configuration->writeConfig($this->configPath, 'apache');
    }

    /**
     * Removes the host from apach configuration.
     *
     * @param string $hostname
     *    Hostname of environment.
     * @param \Cdev\Local\Environment\System\Config\ConfigHelper $config
     *    Configuration helper class to get various configuration options from.
     */
    public function removeHost($hostname) {
        $this->loadApacheConfigFile($this->configPath);
        $i = 0;
        while($item = $this->_root_node->getItem('comment', null, null, null, $i++)) {
            // Comment doesn't contain the hostname so skip it.
            if (strpos($item->content, $hostname) === FALSE) {
                continue;
            }

            // Remove comment.
            $item->removeItem();
        }

        $i = 0;
        while ($item = $this->_root_node->getItem('section', 'VirtualHost', null, null, $i++)) {
            $matches = $this->childHostnameMatches($item, $hostname);

            if ($matches) {
                $item->removeItem();
            }            
        }

        // Remove any blank lines at bottom of file.
        $this->removeLastLineIfBlank($this->_root_node->children);

        $this->_configuration->writeConfig($this->configPath, 'apache');
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

    /**
     * Takes version and path to dev folder and converts it into
     * a ProxyPassMatch directive in Apache.
     *
     * @param string $version
     *    Version number in format "7.2" etc.
     * @param string $path
     *    Path to project.
     * @return string
     */
    private function parseListenLine($version, $path) {
        $portNumber = $this->phpVersionToPortNumber($version);

        return "^/(.*\.php(/.*)?)$ fcgi://127.0.0.1:$portNumber$path/$1";
    }

    /**
     * Converts the current PHP version into a port number
     * so that it can be used in apache config.
     *
     * @param string $version
     *    Version in format "7.2" etc
     * @return string
     */
    private function phpVersionToPortNumber($version) {
        $parsedVersion = intval(str_replace('.', '', $version));
        if ($parsedVersion < 100) {
            $parsedVersion = '0'.$parsedVersion;
        }

        return '9' . $parsedVersion;
    }

    /**
     * Checks if child hostnames match the provided hostname.
     *
     * @param \ConfigContainer $item
     * @param string $hostname
     * @return bool
     */
    private function childHostnameMatches($item, $hostname) {
        $isMatch = false;
        foreach ($item->children as $child) {
            if (strpos($child->content, $hostname) === FALSE) {
                continue;
            }

            $isMatch = true;
        }

        return $isMatch;
    }

    /**
     * Removes the last line of the apache config if it's blank.
     *
     * @param \ConfigContainer $children
     */
    private function removeLastLineIfBlank(&$children) {
        foreach ($children as $key => &$child) {
            if ($key == (count($this->_root_node->children) - 1) && $child->type == 'blank') {
                $child->removeItem();
            }
        }
    }
}