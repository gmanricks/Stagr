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
            ->addOption('enable-short-tags', null, InputOption::VALUE_NONE, 'Property to enable PHP\'s short open tag')
            ->addOption('enable-phalcon', null, InputOption::VALUE_NONE, 'Property to enable the Phalcon framework')
            ->addOption('enable-yaf', null, InputOption::VALUE_NONE, 'Property to enable the Yaf framework')
            ->addOption('disable-short-tags', null, InputOption::VALUE_NONE, 'Property to disable PHP\'s short open tag')
            ->addOption('disable-phalcon', null, InputOption::VALUE_NONE, 'Property to disable the Phalcon framework')
            ->addOption('disable-yaf', null, InputOption::VALUE_NONE, 'Property to disable the Yaf framework');
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
        $this->enablePhalcon($settings, $input);
        $this->enableYaf($settings, $input);
        $this->disableShortTags($settings, $input);
        $this->disablePhalcon($settings, $input);
        $this->disableYaf($settings, $input);

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
                $settings['timezone'] = $timezone;
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
                $settings['exec-time'] = max(intval($execTime), 0);
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
                $settings['memory-limit'] = $memoryLimit;
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
                $settings['upload-size'] = $uploadSize;
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
                $settings['post-size'] = $postSize;
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
            //TODO: Make sure it's valid PHP output buffering size

            $settings['output-buffering'] = $outputBuffering;

            //TODO: Update PHP's POST output buffering size
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
            $settings['short-tags'] = 'On';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and enabling Phalcon
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enablePhalcon(&$settings, &$input)
    {
        if ($input->getOption('enable-phalcon')) {
            $settings['phalcon'] = 'On';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and enabling Yaf
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enableYaf(&$settings, &$input)
    {
        if ($input->getOption('enable-yaf')) {
            $settings['yaf'] = 'On';
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
            $settings['short-tags'] = 'Off';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling Phalcon
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disablePhalcon(&$settings, &$input)
    {
        if ($input->getOption('disable-phalcon')) {
            $settings['phalcon'] = 'Off';
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling Yaf
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disableYaf(&$settings, &$input)
    {
        if ($input->getOption('disable-yaf')) {
            $settings['yaf'] = 'Off';
            $this->updateFpm = true;
        }
    }
}
