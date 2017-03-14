<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LookupAmazonPrice extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setDescription("Lookup the image for a amazon product by ASIN")
            ->setName('amazon:price')
            ->addArgument('asin', InputArgument::REQUIRED);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $asin = $input->getArgument('asin');
        $lookup = $this->container->get('amazon.lookup');


        $price = $lookup->lookupPrice($asin);

        if (empty($price['size'])) {
            $output->writeln(sprintf("<comment>Product has price, but no size: %s %s</comment>",  $price['price'], $price['currency']));
        } else {
            $formattedPrice = sprintf("%s %s / %s", $price['price'], $price['currency'], $price['size']);
            $output->writeln("<comment>$formattedPrice</comment>");
        }
        
    }
}
