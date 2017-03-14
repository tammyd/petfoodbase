<?php

namespace PetFoodDB\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReparseBrokenCommand extends UpdateRecordCommand
{
    protected $sleep = 2;

    protected function configure()
    {
        $this->setName('data:fix')
            ->setDescription("Reparse and update all records with missing data")
            ->addOption(
                'sleep',
                2000,
                InputOption::VALUE_NONE,
                'Time to sleep between requests, in ms'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output);
        if ($input->getOption('sleep')) {
            $this->sleep = intval($input->getOption('sleep'));
        }

        $missingAsin = $this->getMissingAsin();
        $this->getOutput()->writeln(sprintf("<info>%d</info> records missing ASINs", count($missingAsin)));
        foreach ($missingAsin as $catFood) {
            $id = $catFood->getId();
            $this->getOutput()->writeln(sprintf("<info>%d</info> is missing an ASIN, rescraping...", $id));
            $this->rescrapeId($id);
            usleep($this->sleep * 1000);
        }
        $missingAsin = $this->getMissingAsin();
        $this->getOutput()->writeln(sprintf("<info>%d</info> remaining records still missing ASINs\n\n", count($missingAsin)));

        $missingImages = $this->getMissingImage();
        $this->getOutput()->writeln(sprintf("<info>%d</info> records missing images", count($missingImages)));
        foreach ($missingImages as $catFood) {
            $id = $catFood->getId();
            $this->getOutput()->writeln(sprintf("<info>%d</info> is missing an image, rescraping...", $id));
            $this->rescrapeId($id);
            sleep($this->sleep);
        }
        $this->getOutput()->writeln(sprintf("<info>%d</info> remaining records still missing images", count($missingImages)));

    }
}
