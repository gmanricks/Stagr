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
use Stagr\Tools\Cmd;

/**
 * Updates Stagr from latest git repository
 */
class SelfUpdateCommand extends _Command
{
    const DEFAULT_STAGR_REPO   = 'https://github.com/gmanricks/Stagr';
    const DEFAULT_STAGR_BRANCH = 'master';
    const DEFAULT_STAGR_DIR    = '/vagrant';
    const STAGR_INSTALL_DIR    = '/opt/stagr/lib/Stagr';
    const STAGR_EXEC_FILE      = '/usr/bin/stagr';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Performs self update for Stagr')
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Directory where stagr is installed', '')
            ->addOption('repo', null, InputOption::VALUE_OPTIONAL, 'URL to Stagr repository', '')
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Branch of the Stagr repo', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stagrDir    = $input->getOption('dir');
        $stagrRepo   = $input->getOption('repo');
        $stagrBranch = $input->getOption('branch');
        $this->performSelfUpdate($stagrDir, $stagrRepo, $stagrBranch, $output, false);
    }

    /**
     * Actually performs the self-update
     *
     * @param string $stagrDir     Directory for the Stagr repo
     * @param string $stagrRepo    URL for the Stagr repo
     * @param string $stagrBranch  Branch of the Stagr repo
     *
     * @throws \RuntimeException
     */
    public function performSelfUpdate($stagrDir = '', $stagrRepo = '', $stagrBranch = '', OutputInterface $output = null, $reExecute = true)
    {
        $updated  = false;
        $app      = $this->getApplication()->getContainer();
        $settings = $app->configParam('self-update') ?: array();

        // init dir
        if (empty($stagrDir)) {
            $settings['dir'] = $stagrDir = isset($settings['dir']) && !empty($settings['dir'])
                ? $settings['dir']
                : self::DEFAULT_STAGR_DIR;
        } else {
            $settings['dir'] = $stagrDir;
        }
        $stagrDirEsc = escapeshellarg($stagrDir);

        // init repo
        if (empty($stagrRepo)) {
            $settings['repo'] = $stagrRepo = isset($settings['repo']) && !empty($settings['repo'])
                ? $settings['repo']
                : self::DEFAULT_STAGR_REPO;
        } else {
            $settings['repo'] = $stagrRepo;
        }
        $stagrRepo = escapeshellarg($stagrRepo);

        // init branch
        if (empty($stagrBranch)) {
            $settings['branch'] = $stagrBranch = isset($settings['branch']) && !empty($settings['branch'])
                ? $settings['branch']
                : self::DEFAULT_STAGR_BRANCH;
        } else {
            $settings['branch'] = $stagrBranch;
        }
        $stagrBranch = escapeshellarg($stagrBranch);

        $app->configParam('self-update', $settings);

        // dir not existing -> clone now
        if (!is_dir($stagrDir)) {
            if ($output) {
                $output->writeln('<info>Clone Stagr</info>');
            }
            Cmd::run("mkdir -p $stagrDirEsc");
            chdir($stagrDir);
            Cmd::run("git clone $stagrRepo . && git checkout $stagrBranch 2>&1");
            $updated = true;
        }

        // just update
        else {
            chdir($stagrDir);
            if ($output) {
                $output->writeln('<info>Try update Stagr</info>');
            }
            $checkRemote = Cmd::run("git remote | grep '^stagr-update\$' | wc -l");
            if (!$checkRemote) {
                Cmd::run("git remote add stagr-update $stagrRepo");
            } else {
                Cmd::run("git remote set-url stagr-update $stagrRepo");
            }
            $checkBranch = Cmd::run("git branch -a | grep ' '$stagrBranch'\$' | wc -l");
            if (!$checkBranch) {
                if (!Cmd::runCheck("git checkout -b $stagrBranch stagr-update/$stagrBranch")) {
                    throw new \RuntimeException("Failed to switch to branch '$stagrBranch'.");
                }
            } else {
                if (!Cmd::runCheck("git checkout $stagrBranch")) {
                    throw new \RuntimeException("Failed to switch to branch '$stagrBranch'. Do you have uncommited changes?");
                }
            }
            if (!Cmd::runCheck("git pull stagr-update $stagrBranch && echo OK || echo FAIL")) {
                throw new \RuntimeException("Failed pull '$stagrBranch' from '$stagrRepo'");
            }
        }

        // having updates
        if ($updated) {
            if ($output) {
                $output->writeln('<info>Stagr updated -> re-init</info>');
            }
            if (!Cmd::runCheck("rsync -ap --delete-after --exclude=.git $stagrDir/files/stagr-libs/Stagr/ ". self::STAGR_INSTALL_DIR. "/ 1>/dev/null 2>/dev/nullL")) {
                throw new \RuntimeException("Failed to sync Stagr after update");
            }
            Cmd::run("cp $stagrDir/files/cilex.phar ". self::STAGR_INSTALL_DIR. "/cilex.phar");
            Cmd::run("cp $stagrDir/files/stagr.php ". self::STAGR_EXEC_FILE);

            $newArgs = $_SERVER['argv'];
            array_shift($newArgs);
            if ($reExecute) {
                Cmd::runDetach($_SERVER['PHP_SELF'], $newArgs, $_ENV);
            } elseif ($output) {
                $output->writeln('<info>Update performed</info>');
            }
        } elseif ($output) {
            $output->writeln('<info>Update not required.</info>');
        }
    }


}