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
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo();

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

        // init next
        $this->initEmailAndSsh($output);
    }

    /**
     * The long description
     *
     * @param Some\Class   $arg1  What it contains
     * @param string       $arg2  What it contains
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function initEmailAndSsh(OutputInterface &$output)
    {
        $app = $this->getApplication()->getContainer();

        // assure email
        if (!$app->configParam('email')) {
            $email = $this->readStdin($output, '<question>Please enter your E-Mail:</question> ');
            $app->configParam('email', $email);
        }

        // assure ssh key
        if (!$app->configParam('sshkeys')) {
            $sshKey = $this->readStdin($output, '<question>Please enter your SSH public key:</question> ');
            $app->configParam('sshkeys', [$sshKey]);
            file_put_contents(Setup::STAGR_HOME_DIR.'/.ssh/authorized_keys', $sshKey);
        }
    }


}