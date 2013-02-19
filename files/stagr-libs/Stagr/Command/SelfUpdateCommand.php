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
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Directory where stagr is installed', self::DEFAULT_STAGR_DIR)
            ->addOption('repo', null, InputOption::VALUE_OPTIONAL, 'URL to Stagr repository', self::DEFAULT_STAGR_REPO)
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Branch of the Stagr repo', self::DEFAULT_STAGR_BRANCH);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stagrDir    = $input->getOption('dir');
        $stagrDirEsc = escapeshellarg($stagrDir);
        $stagrRepo   = escapeshellarg($input->getOption('repo'));
        $stagrBranch = escapeshellarg($input->getOption('branch'));
        $updated     = false;

        // dir not existing -> clone now
        if (!is_dir($stagrDir)) {
            $output->writeln('<info>Clone Stagr</info>');
            exec("mkdir -p $stagrDirEsc");
            chdir($stagrDir);
            exec("git clone $stagrRepo . && git checkout $stagrBranch 2>&1");
            $updated = true;
        }

        // just update
        else {
            chdir($stagrDir);
            $output->writeln('<info>Try update Stagr</info>');
            $checkRemote = exec("git remote | grep ' stagr-update\$' | wc -l");
            if (!$checkRemote) {
                exec("git remote add stagr-update $stagrRepo");
            } else {
                exec("git remote set-url stagr-update $stagrRepo");
            }
            $checkBranch = exec("git branch -a | grep ' '$stagrBranch'\$' |Wc -l");
            if (!$checkBranch) {
                $switchResponse = exec("git checkout -b $stagrBranch stagr-update/$stagrBranch && echo OK || echo FAIL");
                if (strpos($switchResponse, 'FAIL') !== false) {
                    throw new \RuntimeException("Failed to switch to branch '$stagrBranch'");
                }
            } else {
                exec("git checkout $stagrBranch");
            }
            $pullResponse = exec("git pull stagr-update $stagrBranch && echo OK || echo FAIL")
            if (strpos($pullResponse, 'FAIL') !== false) {
                throw new \RuntimeException("Failed pull '$stagrBranch' from '$stagrRepo'");
            }
            echo 'PULL RESPONSE "'. $pullResponse. '"'. "\n";
        }

        // having updates
        if ($updated) {
            $output->writeln('<info>Stagr updated -> re-init</info>');
            $res = exec("rsync -ap --delete-after --exclude=.git $stagrDir/files/stagr-libs/Stagr/ ". self::STAGR_INSTALL_DIR. "/ 1>/dev/null 2>/dev/null && echo OK || echo FAIL");
            if ($res != "OK") {
                throw new \RuntimeException("Failed to sync Stagr after update");
            }
            exec("cp $stagrDir/files/cilex.phar ". self::STAGR_INSTALL_DIR. "/cilex.phar");
            exec("cp $stagrDir/files/stagr.php ". self::STAGR_EXEC_FILE);

            $newArgs = $_SERVER['argv'];
            array_shift($newArgs);
            pcntl_exec($_SERVER['PHP_SELF'], $newArgs, $_ENV);
        } else {
            echo "Not updated, no replace\n";
        }
    }


}