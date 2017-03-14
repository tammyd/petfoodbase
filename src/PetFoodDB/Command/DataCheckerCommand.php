<?php

namespace PetFoodDB\Command;

use PetFoodDB\Command\Traits\CommandIOTrait;
use PetFoodDB\Model\CatFood;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataCheckerCommand extends ContainerAwareCommand
{
    use CommandIOTrait;

    protected $catfoodService;

    protected function configure()
    {
        $this
            ->setDescription("Check database for errors")
            ->setName('db:check')
            ->addOption('images', null, InputOption::VALUE_NONE)
            ->addOption('asin', null, InputOption::VALUE_NONE);
    }

    protected function setup(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input)->setOutput($output);
        $this->catfoodService = $this->container->catfood;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output);

        $missingAsin = $this->getMissingAsin();
        $missingImage = $this->getMissingImage();
        $incorrectData = $this->getIncorrectNutrition();
        $emptyIngredients = $this->getEmptyIngredients();

        $img = $input->getOption('images') ? true : false;
        $asin = $input->getOption('asin') ? true : false;
        $showAll = !($img || $asin);


        if (!empty($missingAsin) && ($showAll || $asin)) {
            $output->writeln("<info>The following ".count($missingAsin)." records are missing an ASIN:</info>");
            foreach ($missingAsin as $catfood) {
                $output->writeln(sprintf("  %s", $this->outputRecord($catfood)));
            }
        }
        if (!empty($missingImage) && ($showAll || $img)) {
            $output->writeln("<info>The following ".count($missingImage)." records are missing an image:</info>");
            foreach ($missingImage as $catfood) {
                $output->writeln(sprintf("  %s", $this->outputRecord($catfood)));
            }
        }
        if (!empty($incorrectData) && ($showAll))  {
            $output->writeln("<info>The following ".count($incorrectData)." records have incorrect data:</info>");
            foreach ($incorrectData as $catfood) {
                $output->writeln(sprintf("  %s", $this->outputRecord($catfood)));
            }
        }
        if (!empty($emptyIngredients) && ($showAll)) {
            $output->writeln("<info>The following ".count($incorrectData)." records are missing ingredients:</info>");
            foreach ($emptyIngredients as $catfood) {
                $output->writeln(sprintf("  %s", $this->outputRecord($catfood)));
            }
        }
    }

    protected function outputRecord(CatFood $record)
    {
        return sprintf("%d:\t%s", $record->getId(), $record->getDisplayName());
    }

    protected function getMissingAsin()
    {
        $data = $this->catfoodService->getDb()->catfood
            ->select("*")
            ->where('asin', "")
            ->or('asin', null);

        return $this->catfoodService->convertResultToCatfood($data);
    }

    protected function getMissingImage()
    {
        $data = $this->catfoodService->getDb()->catfood
            ->select("*")
            ->where('imageUrl', "")
            ->or('imageUrl', null);

        return $this->catfoodService->convertResultToCatfood($data);
    }

    protected function getIncorrectNutrition()
    {
        $totalQuery = $this->catfoodService->getDb()->catfood
            ->select("(catfood.ash + catfood.moisture + catfood.protein + catfood.fat + catfood.fibre) as total, *")
            ->where('total > 105')
            ->or('total < 1');

        $badValueQuery = $this->catfoodService->getDb()->catfood
            ->select("*")
            ->where('catfood.protein < 0.01')
            ->or('catfood.fat < 0.01')
            ->or('catfood.moisture < 0.01');

        $totalData = $this->catfoodService->convertResultToCatfood($totalQuery);
        $proteinData = $this->catfoodService->convertResultToCatfood($badValueQuery);

        $result = array_merge($totalData, $proteinData);

        return $result;
    }

    protected function getEmptyIngredients()
    {
        $data = $this->catfoodService->getDb()->catfood
            ->select("*")
            ->where('length(ingredients) <= 2');

        return $this->catfoodService->convertResultToCatfood($data);
    }

}
