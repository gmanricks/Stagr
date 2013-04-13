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
use Stagr\Tools\Setup;

/**
 * List apps which have been setup with Stagr
 */
class ListCommand extends _Command
{
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription('List apps which have been setup with Stagr')
            ->addOption('details', null, InputOption::VALUE_NONE, 'Show detailed App configurations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo('Show');

        // check root
        if (posix_geteuid() !== 0) {
            throw new \LogicException("Use 'sudo stagr'!");
        }

        $app = $this->getApplication();
        $installedApps = $app->configParam('apps');
        if (is_null($installedApps) || empty($installedApps)) {
            $output->writeln('<error>No Apps found</error>');
            return;
        }

        $showDetails = $input->getOption('details');
        ksort($installedApps);
        foreach ($installedApps as $appName => $appData) {
            if ($showDetails) {
                $output->writeln('[<info>'. $appName. '</info>]');

                $output->writeln("\n  General:");
                $output->writeln(
                    '    <comment>'. sprintf('%-30s', 'Docroot'). '</comment>: '
                    . sprintf(Setup::APP_WWW_DIR_TMPL, $appName). '/htdocs'
                    . ($appData['doc-root'] ? '/'. $appData['doc-root'] : '')
                );

                $output->writeln("\n  PHP:");
                ksort($appData['php']);
                foreach ($appData['php'] as $key => $value) {
                    $output->writeln('    <comment>'. sprintf('%-30s', preg_replace('/-/', '.', $key)). '</comment>: '. $value);
                }

                $output->writeln("\n  ENV:");
                $appData['env']['APP_NAME'] = $appName;
                ksort($appData['env']);
                foreach ($appData['env'] as $key => $value) {
                    $output->writeln('    <comment>'. sprintf('%-30s', $key). '</comment>: '. $value);
                }

                $output->writeln("\n");
            } else {
                $output->writeln('* '. $appName);
            }
        }
        $output->write("\n");
    }

}
