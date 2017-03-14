<?php

namespace PetFoodDB\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReparseBrandCommand extends UpdateRecordCommand
{
    protected function configure()
    {
        $this->setName('data:reparse')
            ->setDescription("Reparse all brand records")
            ->addArgument(
                'brand',
                InputArgument::REQUIRED,
                "brand to reparse"
            )->addOption(
                'product',
                null,
                InputOption::VALUE_NONE,
                "Just reparse titles"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output);
        $brand = $input->getArgument('brand');

        $catFoods = $this->catfoodService->textSearch("brand:$brand");
        foreach ($catFoods as $catFood) {
            $id = $catFood->getId();
            $this->rescrapeId($id);
        }

    }
}
