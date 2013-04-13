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
use Symfony\Component\Console\Command\HelpCommand as _HelpCommand;

/**
 * HelpCommand displays the help for a given command. Modified version so it works without the annoying list command.
 */
class HelpCommand extends _HelpCommand
{
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('help')
            ->setDefinition(array(
                new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', 'help'),
                new InputOption('xml', null, InputOption::VALUE_NONE, 'To output help as XML'),
            ))
            ->setDescription('Displays help for a command')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% list</info>

ARRRR You can also output the help as XML by using the <comment>--xml</comment> option:

  <info>php %command.full_name% --xml list</info>

To display the list of available commands, please use the <info>list</info> command.
EOF
            )
        ;
    }

    /**
     * Sets the command
     *
     * @param Command $command The command to set
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandName = $input->getArgument('command_name');
        if ($commandName === 'help') {
            if ($input->getOption('xml')) {
                $output->writeln($this->getApplication()->asXml(), OutputInterface::OUTPUT_RAW);
            } else {
                $output->writeln($this->getApplication()->asText());
            }
        } else {
            $this->command = $this->getApplication()->find($commandName);
            if ($input->getOption('xml')) {
                $output->writeln($this->command->asXml(), OutputInterface::OUTPUT_RAW);
            } else {
                $output->writeln($this->command->asText());
            }
        }

        $this->command = null;
    }
}
