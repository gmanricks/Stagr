<?php

/*
 * This file is part of the Stagr framework.
 *
 * (c) Gabriel Manricks <gmanricks@me.com>
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stagr\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Shell;
use Stagr\Tools\Setup;

/**
 * Example command for testing purposes.
 */
class SetCommand extends _Command
{

    private $updateFpm;
    private $updateApache;
    private $updateGit;

    protected function configure()
    {
        $this
            ->setName('set')
            ->setDescription('Command to adjust specific settings for an app')
            ->addArgument('app', InputArgument::REQUIRED, 'The name of the app to modify')
            ->addOption('env', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Use this to set [multiple] the enviroment variables')
            ->addOption('webcall', null, InputOption::VALUE_REQUIRED, 'This property sets the URL for the Webcall trigger')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s Timezone')
            ->addOption('exec-time', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s max execution time')
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s memory Limit')
            ->addOption('upload-size', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s max upload size')
            ->addOption('post-size', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s max post size')
            ->addOption('output-buffering', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s output buffering size')
            ->addOption('doc-root', null, InputOption::VALUE_REQUIRED, 'This property sets the document root')
            ->addOption('enable-short-tags', null, InputOption::VALUE_NONE, 'Property to enable PHP\'s short open tag')
            ->addOption('disable-short-tags', null, InputOption::VALUE_NONE, 'Property to disable PHP\'s short open tag')
            ->addOption('restore-defaults', null, InputOption::VALUE_NONE, 'Use this option to restore all defaults');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo();

        // check root
        if (posix_geteuid() !== 0) {
            throw new \LogicException("Use 'sudo stagr'!");
        }

        // initialize some variables
        $appName = $input->getArgument('app');
        $app = $this->getApplication()->getContainer();

        $settings = (is_array($app->configParam($appName))) ? $app->configParam($appName) : array();
    
        //Initialize rebuild booleans
        $this->updateFpm = false;
        $this->updateApache = false;
        $this->updateGit = false;


        // proccess CLI options
        $this->setEnviromentVars($settings, $input);
        $this->setWebcall($settings, $input);
        $this->setTimezone($settings, $input);
        $this->setExecTime($settings, $input);
        $this->setMemoryLimit($settings, $input);
        $this->setUploadSize($settings, $input);
        $this->setPostSize($settings, $input);
        $this->setOutputBuffering($settings, $input);
        $this->enableShortTags($settings, $input);
        $this->disableShortTags($settings, $input);
        $this->setDocRoot($settings, $input);
        $this->restoreDefaults($settings, $input);

        // save settings
        $app->configParam($appName, $settings);

        $setup = new Setup($appName, $output, $this);

        if ($this->updateFpm) {
            $setup->rebuildFpmConfig();
        }

        if ($this->updateApache) {
            $setup->rebuildVhost();
        }

        if ($this->updateApache || $this->updateFpm) {
            $setup->restartServices();
        }
    }

    /**
     * Function for checking and setting PHP's env vars
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setEnviromentVars(&$settings, &$input)
    {
        if ($env = $input->getOption('env')) {

            $vars = array();

            foreach ($env as $str) {
                $raw = explode("=", $str);
                if (!count($raw) === 2) {
                    $raw = explode(":", $str);
                }
                if (count($raw) === 2) {
                    array_push($vars, array($raw[0] => $raw[1]));
                }
            }

            $settings['env'] = $vars;

            $this->updateApache = true;
        }
    }

    /**
     * Function for checking and setting Webcall trigger
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setWebcall(&$settings, &$input)
    {
        if ($webcall = $input->getOption('webcall')) {
            //TODO: Test if valid URL Maybe?

            $settings['webcall'] = $webcall;

            //TODO: Update GIT post-recieve hook, or make hook read from Yaml file
        }
    }

    /**
     * Function for checking and setting PHP's Timezone
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setTimezone(&$settings, &$input)
    {
        if ($timezone = $input->getOption('timezone')) {
            
            //Validate timezone
            $timezoneValidate = timezone_open($timezone);
            
            if ($timezoneValidate) {
                $settings['date-timezone'] = $timezone;
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's Max execution time
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setExecTime(&$settings, &$input)
    {
        if ($execTime = $input->getOption('exec-time')) {
            
            //Validates that it's a number
            if (is_numeric($execTime)) {
                $settings['max_execution_time'] = max(intval($execTime), 0);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's Memory Limit
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setMemoryLimit(&$settings, &$input)
    {
        if ($memoryLimit = $input->getOption('memory-limit')) {
            
            //Validate that it is a valid memory size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $memoryLimit)) {
                $settings['memory_limit'] = $memoryLimit;
                $settings['apc-shm_size'] = $memoryLimit;
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's max upload size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setUploadSize(&$settings, &$input)
    {
        if ($uploadSize = $input->getOption('upload-size')) {

            //Validate that it is a valid upload size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $uploadSize)) {
                $settings['upload_max_filesize'] = $uploadSize;
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's max POST size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setPostSize(&$settings, &$input)
    {
        if ($postSize = $input->getOption('post-size')) {
            
            //Validate that it is a valid post size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $postSize)) {
                $settings['post_max_size'] = $postSize;
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's output buffering size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setOutputBuffering(&$settings, &$input)
    {
        if ($outputBuffering = $input->getOption('output-buffering')) {
            if (is_numeric($outputBuffering)) {
                $settings['output_buffering'] = intval($outputBuffering);
                $this->updateFpm = true;
            } elseif ($outputBuffering === "On" || $outputBuffering === "Off") {
                $settings['output_buffering'] = $outputBuffering;
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for setting doc root
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setDocRoot(&$settings, &$input)
    {
        if ($docRoot = $input->getOption('doc-root')) {
            if (preg_match('/^[0-9a-zA-Z_\-\/]+$/', $docRoot)) {
                $settings['doc-root'] = $docRoot;
                $this->updateApache = true;
            }
        }
    }

    /**
     * Function for checking and enabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enableShortTags(&$settings, &$input)
    {
        if ($input->getOption('enable-short-tags')) {
            $settings['short_open_tag'] = 'On';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disableShortTags(&$settings, &$input)
    {
        if ($input->getOption('disable-short-tags')) {
            $settings['short_open_tag'] = 'Off';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function restoreDefaults(&$settings, &$input)
    {
        if ($input->getOption('restore-defaults')) {
            $settings = array(
                'env' => array(),
                'webcall' => false,
                'date-timezone' => 'Europe/Berlin',
                'max_execution_time' => 300,
                'memory_limit' => '64M',
                'apc-shm_size' => '64M',
                'upload_max_filesize' => '128M',
                'post_max_size' => '128M',
                'short_open_tag' => 'On',
                'output_buffering' => 4096,
                'doc-root' => ''
            );
            $this->updateFpm = true;
            $this->updateApache = true;
        }
    }
}
