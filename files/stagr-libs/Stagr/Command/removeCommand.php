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
class RemoveCommand extends _Command
{
    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Command to remove an App')
            ->addArgument('app', InputArgument::REQUIRED, 'The name of the app to modify');
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

        $app->unsetParam($appName);

        $output->write("Removing All Files ... ");
        exec("rm -R /var/www/web/$appName/ /var/fpm/socks/$appName /var/fpm/prepend/$appName");
        exec("rm /etc/apache2/sites-enabled/$appName /etc/apache2/sites-available/$appName");
        $output->writeln("<info>OK</info>");

        $output->write("Removing MySQL User and Database ... ");

        $dbh = new \PDO('mysql:host=localhost;dbname=mysql', 'root');
        $sth = $dbh->prepare('DROP DATABASE ?');
        $sth->bindParam(1, $appName);
        $sth->execute();

        $sth = $dbh->prepare('DELETE FROM user WHERE User=?');
        $sth->bindParam(1, $appName);
        $sth->execute();

        $sth = $dbh->prepare('DELETE FROM db WHERE User=?');
        $sth->bindParam(1, $appName);
        $sth->execute();

        $dbh->query('FLUSH PRIVILEGES');
        $output->writeln("<info>OK</info>");

        $output->write("Removing GIT Repo ... ");
        exec("rm -R /home/vagrant/" . $appName . ".git");
        $output->writeln("<info>OK</info>");

        //remove site from hosts
    }
}
