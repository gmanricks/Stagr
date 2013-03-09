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
use Stagr\Tools\Setup;
use Stagr\Tools\Cmd;

/**
 * Example command for testing purposes.
 */
class RemoveCommand extends _Command
{

    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Remove an App')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the App');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo('Remove');

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

        if (!is_dir(sprintf(Setup::APP_WWW_DIR_TMPL, $appName))) {
            $output->writeln("That App doesn't seem to exist.");
            exit(0);
        }

        // make sure this is intentional
        $this->readStdin($output, 'Type DELETE if you are sure> ', function ($in) use($output) {
            if ($in !== 'DELETE') {
                $output->writeln("Aborted");
                exit(0);
            }
            return true;
        }, false, '');

        $output->writeln("\n\nRemove {$appName}\n----------");

        // remove from config
        $app = $this->getApplication()->getContainer();
        $output->write('Remove App from config: ');
        $app->unsetParam("apps.$appName");
        $output->writeln('<info>OK</info>');

        // remove dirs
        $output->write('Remove App directories: ');
        foreach (array(
            sprintf(Setup::APP_GIT_DIR_TMPL, $appName),
            sprintf(Setup::APP_WWW_DIR_TMPL, $appName),
            sprintf(Setup::APP_FPM_SOCK_DIR_TMPL, $appName),
            sprintf(Setup::APP_FPM_PREPEND_DIR_TMPL, $appName)
        ) as $dir) {
            if (is_dir($dir)) {
                Cmd::run(sprintf('rm -rf "%s"', $dir));
            }
        }
        $output->writeln('<info>OK</info>');

        // remove files & links
        $output->write('Remove App config files: ');
        foreach (array(
            "/etc/apache2/sites-enabled/$appName",
            "/etc/apache2/sites-available/$appName",
            "/etc/php5/pool.d/$appName.conf",
        ) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $output->writeln('<info>OK</info>');

        // remove database and user from mysl
        $output->write('Remove App from MySQL: ');
        $dbh = new \PDO('mysql:host=localhost;dbname=mysql', 'root');
        $dbh->query('DROP DATABASE '. $appName);

        $sth = $dbh->prepare('DELETE FROM user WHERE User = ?');
        $sth->bindParam(1, $appName);
        $sth->execute();

        $sth = $dbh->prepare('DELETE FROM db WHERE User = ?');
        $sth->bindParam(1, $appName);
        $sth->execute();

        $dbh->query('FLUSH PRIVILEGES');
        $output->writeln('<info>OK</info>');

        $setup = new Setup($appName, $output, $this);
        $setup->restartServices();

    }
}
