<?php

namespace PetFoodDB\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeAllCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription("Scrape all defined cat food scrapers")
            ->setName('scrape:all')
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_REQUIRED,
                'Time to sleep between requests, in ms'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'output the commands'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = "";
        try {
            $command = $this->getApplication()->find('scrape');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
        }
        $commands = $this->parseErrorMessageForCommands($message);

        $sleep = $input->getOption('sleep');
        foreach ($commands as $commandName) {
            if ($commandName == $this->getName()) {
                continue;
            }

            if ($input->getOption('list')) {
                $output->writeln("<info>$commandName</info>");
                continue;
            }

            $commandInput = new ArrayInput(['command'=>$commandName]);
            if ($sleep) {
                $commandInput['--sleep'] = $input->getOption('sleep');
            }

            $output->writeln("<info>***</info> Starting <info>$commandName</info>");
            $command = $this->getApplication()->find($commandName);
            $command->run($commandInput, $output);
        }
    }

    protected function parseErrorMessageForCommands($message)
    {
        $pieces = explode("\n", $message);
        $commands = array_map('trim', array_slice($pieces, 3));
        sort($commands);

        return $commands;
    }

}
