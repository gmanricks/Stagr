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
        $app = $this->getArgument('app');
        $settings = (is_array($this->configParam($app))) ? $this->configParam($app) : array();
        
        // proccess CLI options
        $settings = $this->setEnviromentVars($settings);
        $settings = $this->setWebcall($settings);
        $settings = $this->setTimezone($settings);
        $settings = $this->setExecTime($settings);
        $settings = $this->setMemoryLimit($settings);
        $settings = $this->setUploadSize($settings);
        $settings = $this->setPostSize($settings);
        $settings = $this->setOutputBuffering($settings);
        $settings = $this->enableShortTags($settings);
        $settings = $this->enablePhalcon($settings);
        $settings = $this->enableYaf($settings);
        $settings = $this->disableShortTags($settings);
        $settings = $this->disablePhalcon($settings);
        $settings = $this->disableYaf($settings);

        // save settings
        $this->configParam($app, $settings);
    }

    /**
     * Function for checking and setting PHP's env vars 
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setEnviromentVars($settings)
    {
        if ($env = $input->getOption('env')) {
            //TODO: Do some input validation

            $settings['env'] = $env;

            //TODO: Set the PHP env vars foreach
        }
        return $settings;
    }

    /**
     * Function for checking and setting Webcall trigger
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setWebcall($settings)
    {
        if ($webcall = $input->getOption('webcall')) {
            //TODO: Test if valid URL Maybe?

            $settings['webcall'] = $webcall;

            //TODO: Update GIT post-recieve hook, or make hook read from Yaml file
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's Timezone
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setTimezone($settings)
    {
        if ($timezone = $input->getOption('timezone')) {
            //TODO: Make sure timezone is valid for PHP

            $settings['timezone'] = $timezone;

            //TODO: Update PHP's Timezone
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's Max execution time
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setExecTime($settings)
    {
        if ($execTime = $input->getOption('exec-time')) {
            //TODO: Make sure it's valid PHP exec-time

            $settings['exec-time'] = $execTime;

            //TODO: Update PHP's max exec time
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's Memory Limit
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setMemoryLimit($settings)
    {
        if ($memoryLimit = $input->getOption('memory-limit')) {
            //TODO: Make sure it's valid PHP memory limit

            $settings['memory-limit'] = $memoryLimit;

            //TODO: Update PHP's max memory limit
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's max upload size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setUploadSize($settings)
    {
        if ($uploadSize = $input->getOption('upload-size')) {
            //TODO: Make sure it's valid PHP upload size

            $settings['upload-size'] = $uploadSize;

            //TODO: Update PHP's max upload size
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's max POST size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setPostSize($settings)
    {
        if ($postSize = $input->getOption('post-size')) {
            //TODO: Make sure it's valid PHP POST size

            $settings['post-size'] = $postSize;

            //TODO: Update PHP's max POST size
        }
        return $settings;
    }

    /**
     * Function for checking and setting PHP's output buffering size
     *
     * @param  array $settings - app's Settings arr
     */
    protected function setOutputBuffering($settings)
    {
        if ($outputBuffering = $input->getOption('output-buffering')) {
            //TODO: Make sure it's valid PHP output buffering size

            $settings['output-buffering'] = $outputBuffering;

            //TODO: Update PHP's POST output buffering size
        }
        return $settings;
    }

    /**
     * Function for checking and enabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enableShortTags($settings)
    {
        if ($input->getOption('enable-short-tags')) {
            $settings['enable-short-tags'] = true;

            //TODO: Enable PHP Short Tags
        }
        return $settings;
    }

    /**
     * Function for checking and enabling Phalcon
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enablePhalcon($settings)
    {
        if ($input->getOption('enable-phalcon')) {
            $settings['enable-phalcon'] = true;

            //TODO: Enable Phalcon
        }
        return $settings;
    }
    
    /**
     * Function for checking and enabling Yaf
     *
     * @param  array $settings - app's Settings arr
     */
    protected function enableYaf($settings)
    {
        if ($input->getOption('enable-yaf')) {
            $settings['enable-yaf'] = true;

            //TODO: Enable Yaf
        }
        return $settings;
    }

    /**
     * Function for checking and disabling PHP's short tags
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disableShortTags($settings)
    {
        if ($input->getOption('disable-short-tags')) {
            $settings['disable-short-tags'] = true;

            //TODO: Disable PHP Short Tags
        }
        return $settings;
    }

    /**
     * Function for checking and disabling Phalcon
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disablePhalcon($settings)
    {
        if ($input->getOption('disable-phalcon')) {
            $settings['disable-phalcon'] = true;

            //TODO: Disable Phalcon
        }
        return $settings;
    }
    
    /**
     * Function for checking and disabling Yaf
     *
     * @param  array $settings - app's Settings arr
     */
    protected function disableYaf($settings)
    {
        if ($input->getOption('disable-yaf')) {
            $settings['disable-yaf'] = true;

            //TODO: Disable Yaf
        }
        return $settings;
    }
}
