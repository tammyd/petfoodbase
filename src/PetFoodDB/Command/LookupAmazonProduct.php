<?php


namespace PetFoodDB\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LookupAmazonProduct extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setDescription("Lool for amazon products with a given set of keywords")
            ->setName('amazon:product')
            ->addArgument('keywords', InputArgument::REQUIRED);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $keywords = $input->getArgument('keywords');
        $lookup = $this->container->get('amazon.lookup');

        $asin = $lookup->lookupAsinByKeywords($keywords);

        if (!$asin) {
            $output->writeln("<comment>No product found for \"" . $keywords . "\"</comment>");
        } else {
            $url = $this->container->get('config')['amazon.purchase.url.template'];
            $url = sprintf($url, $asin);
            $url = str_replace("catfood00b-20", "", $url);
            $output->writeln("<info>Found ASIN for \"" . $keywords . "\"" . ": $asin. </info>");
            $output->writeln("<info>Url: $url</info>");

            $command = $this->getApplication()->find('amazon:image');
            $arguments = array(
                'command' => 'amazon:image',
                'asin'    => $asin,
            );
            $argInput = new ArrayInput($arguments);
            $command->run($argInput, $output);
        }

    }
}
