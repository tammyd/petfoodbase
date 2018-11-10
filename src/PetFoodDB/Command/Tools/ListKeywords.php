<?php

namespace PetFoodDB\Command\Tools;

use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListKeywords extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription("List all potential keywords for SEO research")
            ->setName('seo:keywords')
            ->addOption("filename", "f", InputOption::VALUE_OPTIONAL, "list of brands to look up")
            ->addOption('adjective', "a", InputOption::VALUE_OPTIONAL, "just use a single adjective, or 'none' for brand list")
            ->addOption('brand', "b", InputOption::VALUE_OPTIONAL, "just use a single brand");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keywords = [];
        $catfoodService = $this->container->get('catfood');
        $justBrands = false;


        if ($input->getOption('filename')) {
            $contents = file_get_contents($input->getOption('filename'));
            $brandNames = array_filter(explode("\n", $contents), 'trim');
            $brands = [];
            foreach ($brandNames as $name) {
                $brands[] = ['brand' => $name];
            }
        } else if ($input->getOption('brand')) {
            $brands[] = ['brand' => $input->getOption('brand')];
        } else {
            $brands = $catfoodService->getBrands();
        }
        if ($input->getOption('adjective')) {
            if ($input->getOption('adjective') == "none") {
                $justBrands = true;
            } else {
                $adjectives = [$input->getOption('adjective')];
            }
        } else {
            $adjectives = [
                'cat food',
                'dry cat food',
                'wet cat food',
                'canned cat food',
                'kitten food',
                'review',
                'reviews',
                'calories',
                'nutrition',
                'cat food review',
                'cat food reviews',
            ];
        }



        foreach ($brands as $brand) {
            if (!$input->getOption('adjective')) {
                //$keywords[] = $brand['brand'];
            }
            if ($justBrands) {
                $keywords[] = strtolower($brand['brand']);
            } else {
                foreach ($adjectives as $adj) {
                    $keywords[] = strtolower(sprintf("%s %s", $brand['brand'], $adj));
                }
            }
        }

        sort($keywords);


        foreach ($keywords as $keyword) {
            $output->writeln($keyword);
        }


    }

}
