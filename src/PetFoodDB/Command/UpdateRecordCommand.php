<?php

namespace PetFoodDB\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRecordCommand extends DataCheckerCommand
{

    protected function configure()
    {
        $this->setName('data:update')
            ->setDescription("Reparse and update a record in the db")
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                "Id in DB to reparse"
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

        $idList = $input->getArgument('id');
        $ids = explode(",", $idList);
        foreach ($ids as $id) {
            $this->rescrapeId(intval(trim($id)));
        }

    }

    protected function rescrapeId($id)
    {
        $data = $this->container->catfood->getById($id);


        if (!$data) {
            $this->getOutput()->writeln("<error>Unable to find catfood with id $id</error>");

            return;
        }

        $parserClass = $data->parserClass;
        $url = $data->getSource();

        if (!$url) {
            $this->getOutput()->writeln("<error>Catfood $id does not have source url to re-parse</error>");
        }

        if (is_subclass_of($parserClass, 'PetFoodDB\Scrapers\NoSitemapScraper')) {
            $parser = new $parserClass(
                $this->container->get('amazon.lookup'),
                $this->container->get('manual.data'),
                $this->container->get('sitemap.utils')
            );
        } elseif (is_subclass_of($parserClass, 'PetFoodDB\Scrapers\BasePetFoodScraper')) {
            $parser = new $parserClass($this->container->get('amazon.lookup'), $this->container->get('manual.data'));
        } else {
            $this->getOutput()->writeln("<error>Cannot create instance of $parserClass</error>");

            return;
        }

        if ($this->getInput()->getOption('product')) {
            //just scrape product info
            $product = $parser->parseProductData($url);
            $catFood = $data;
            $catFood->update($product);

        } else {
            $catFood = $parser->scrapeUrl($url);
        }

        if ($catFood) {
            $catFood->setId($data->getId());
            $catFood->setUpdated(date('Y-m-d H:i:s', time()));

            $result = $this->catfoodService->update($catFood);
        } else {
            $result = -1;
        }



        if ($result==1) {
            $this->getOutput()->writeln("<info>CatFood $id sucessfully updated</info>");
        } else {
            $this->getOutput()->writeln("<error>Error updating Catfood $id.</error>");
        }

    }

}
