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
class SetupCommand extends _Command
{


    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Setup or update an App')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo('Setup');

        // check root
        if (posix_geteuid() !== 0) {
            throw new \LogicException("Use 'sudo stagr'!");
        }

        // read app name
        $appCheck = function ($in) {
            $l = strlen($in);
            return $l > 1 && $l <= 16 && preg_match('/^(?:[a-z0-9]+\-?)+[a-z0-9]$/', $in);
        };
        $appName = $input->getArgument('name');
        if (!$appName || !$appCheck($appName)) {
            $appName = $this->readStdin($output, 'App Name> ', $appCheck, false, 'Invalid name, try again, use [a-z0-9-]');
        }

        $setup = new Setup($appName, $output, $this);
        $setup->initEmailAndSsh();

        $app = $this->getApplication()->getContainer();

        //Set Defaults
        $defaults = array(
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


        $app->configParam($appName, $defaults);

        // setup all
        $output->writeln("\n\nSetup {$appName}\n----------");

        $output->writeln("\n# Webserver");
        $setup->setupWebserver();

        $output->writeln("\n# MySQL");
        $setup->setupMySQL();

        $output->writeln("\n# Git");
        $setup->setupGit();

        // print info
        $output->writeln("\n");
        $setup->printIpInfo();
        $output->writeln("");
        $setup->printGitInfo();
        $output->writeln("");
        $setup->printMySQLInfo();
        $output->writeln("\n");
    }
}
