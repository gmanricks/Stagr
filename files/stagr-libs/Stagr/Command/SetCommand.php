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
    private $appName;
    private $input;
    private $app;

    protected function configure()
    {
        $this
            ->setName('set')
            ->setDescription('Command to adjust specific settings for an app')
            ->addArgument('app', InputArgument::REQUIRED, 'The name of the app to modify')
            ->addOption('env', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Use this to set [multiple] the enviroment variables')
            ->addOption('env-replace', null, InputOption::VALUE_NONE, 'If set, the env variables replace all (pre)existing variables. Otherwise they are just added.')
            ->addOption('webcall', null, InputOption::VALUE_REQUIRED, 'This property sets the URL for the Webcall trigger')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s Timezone')
            ->addOption('exec-time', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s max execution time')
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'This property sets PHP\'s memory Limit')
            ->addOption('apc-size', null, InputOption::VALUE_REQUIRED, 'This property sets APC\'s Cache size')
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
        Setup::printLogo('Set');

        // check root
        if (posix_geteuid() !== 0) {
            throw new \LogicException("Use 'sudo stagr'!");
        }

        // initialize some variables
        $this->appName = $appName = $input->getArgument('app');
        $this->app = $app = $this->getApplication();

        // set only for existing app
        if (!$app->configParam('apps.'. $appName)) {
            throw new \RuntimeException("App '$appName' does not exist. Create first with 'stagr setup \"$appName\"'");
        }

        $this->input = &$input;

        //Initialize rebuild booleans
        $this->updateFpm = false;
        $this->updateApache = false;
        $this->updateGit = false;


        // proccess CLI options
        $this->setEnviromentVars();
        $this->setWebcall();
        $this->setTimezone();
        $this->setExecTime();
        $this->setMemoryLimit();
        $this->setUploadSize();
        $this->setPostSize();
        $this->setOutputBuffering();
        $this->enableShortTags();
        $this->disableShortTags();
        $this->setDocRoot();
        $this->setApcSize();
        $this->restoreDefaults();

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
     */
    protected function setEnviromentVars()
    {
        $envReplace = $this->input->getOption('env-replace');
        if ($env = $this->input->getOption('env')) {
            $vars = array();
            foreach ($env as $str) {
                $raw = explode("=", $str);
                if (count($raw) === 2) {
                    $vars[$raw[0]] = $raw[1];
                    //array_push($vars, array($raw[0] => $raw[1]));
                }
            }
            $this->app->configParam("apps.{$this->appName}.env", $vars, $envReplace);
            $this->updateApache = true;
        } elseif ($envReplace) {
            $this->app->configParam("apps.{$this->appName}.env", array(), true);
        }
    }

    /**
     * Function for checking and setting Webcall trigger
     */
    protected function setWebcall()
    {
        if (!is_null($webcall = $this->input->getOption('webcall'))) {
            //TODO: Test if valid URL Maybe?

            $this->app->configParam("apps.{$this->appName}.hooks.webcall", $webcall ? 1 : 0);

            //TODO: Update GIT post-recieve hook, or make hook read from Yaml file
        }
    }

    /**
     * Function for checking and setting PHP's Timezone
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setTimezone()
    {
        if ($timezone = $this->input->getOption('timezone')) {

            //Validate timezone
            $timezoneValidate = timezone_open($timezone);

            if ($timezoneValidate) {
                $this->app->configParam("apps.{$this->appName}.php.date-timezone", $timezone);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's Max execution time
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setExecTime()
    {
        if ($execTime = $this->input->getOption('exec-time')) {

            //Validates that it's a number
            if (is_numeric($execTime)) {
                $this->app->configParam("apps.{$this->appName}.php.max_execution_time", max(intval($execTime), 0));
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's Memory Limit
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setMemoryLimit()
    {
        if ($memoryLimit = $this->input->getOption('memory-limit')) {

            //Validate that it is a valid memory size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $memoryLimit)) {
                $this->app->configParam("apps.{$this->appName}.php.memory_limit", $memoryLimit);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting APC Cache size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setApcSize()
    {
        if ($apcSize = $this->input->getOption('apc-size')) {

            //Validate that it is a valid memory size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $apcSize)) {
                $this->app->configParam("apps.{$this->appName}.php.apc-shm_size", $apcSize);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's max upload size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setUploadSize()
    {
        if ($uploadSize = $this->input->getOption('upload-size')) {

            //Validate that it is a valid upload size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $uploadSize)) {
                $this->app->configParam("apps.{$this->appName}.php.upload_max_filesize", $uploadSize);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's max POST size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setPostSize()
    {
        if ($postSize = $this->input->getOption('post-size')) {

            //Validate that it is a valid post size parameter
            if (preg_match('/^[0-9]+[KMG]?$/', $postSize)) {
                $this->app->configParam("apps.{$this->appName}.php.post_max_size", $postSize);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for checking and setting PHP's output buffering size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setOutputBuffering()
    {
        if ($outputBuffering = $this->input->getOption('output-buffering')) {
            if (is_numeric($outputBuffering)) {
                $this->app->configParam("apps.{$this->appName}.php.output_buffering", intval($outputBuffering));
                $this->updateFpm = true;
            } elseif ($outputBuffering === "On" || $outputBuffering === "Off") {
                $this->app->configParam("apps.{$this->appName}.php.output_buffering", $outputBuffering);
                $this->updateFpm = true;
            }
        }
    }

    /**
     * Function for setting doc root
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setDocRoot()
    {
        if ($docRoot = $this->input->getOption('doc-root')) {
            if (preg_match('/^[0-9a-zA-Z_\-\/]+$/', $docRoot)) {
                $this->app->configParam("apps.{$this->appName}.doc-root", $docRoot);
                $this->updateApache = true;
            }
        }
    }

    /**
     * Function for checking and enabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enableShortTags()
    {
        if ($this->input->getOption('enable-short-tags')) {
            $this->app->configParam("apps.{$this->appName}.php.short_open_tag", "On");
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disableShortTags()
    {
        if ($this->input->getOption('disable-short-tags')) {
            $this->app->configParam("apps.{$this->appName}.php.short_open_tag", "Off");
            $this->updateFpm = true;
        }
    }

    /**
     * Function for checking and disabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function restoreDefaults()
    {
        if ($this->input->getOption('restore-defaults')) {
            $this->app->configParam("apps.{$this->appName}", Setup::$DEFAULT_SETTINGS, true);
            $this->updateFpm = true;
            $this->updateApache = true;
        }
    }
}
