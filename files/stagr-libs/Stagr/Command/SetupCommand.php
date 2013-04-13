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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the App')
            ->addOption('restore-defaults', null, InputOption::VALUE_NONE, 'Restores App settings to default (for update only)');
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

        $app = $this->getApplication();

        // setup all
        $output->writeln("\n\nSetup {$appName}\n----------");

        // write app defaults (if not setup already)
        if (!($hasSettings = $app->configParam('apps.'. $appName)) || $input->getOption('restore-defaults')) {
            $output->write(($hasSettings ? 'Restore' : 'Write'). ' default settings: ');
            $app->configParam('apps.'. $appName, Setup::$DEFAULT_SETTINGS);
            $output->writeln('<info>OK</info>');
        } else {
            $output->writeln('Keep App settings intact');
        }

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
