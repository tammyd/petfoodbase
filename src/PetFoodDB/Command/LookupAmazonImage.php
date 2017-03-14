<?php


namespace PetFoodDB\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LookupAmazonImage extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setDescription("Lookup the image for a amazon product by ASIN")
            ->setName('amazon:image')
            ->addArgument('asin', InputArgument::REQUIRED)
            ->addOption("silent", "s", InputOption::VALUE_NONE, "Just display url");
    }
    

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $asin = $input->getArgument('asin');
        $lookup = $this->container->get('amazon.lookup');
        

        $image = $lookup->lookupImageUrlByAsin($asin);

        $silent = $input->getOption('silent');


        if (!$image) {
            if (!$silent) {
                $output->writeln("<comment>No image found for ASIN " . $asin . "</comment>");
            }
        } else {
            if (!$silent) {
                $output->writeln("<info>Found image for ASIN $asin: $image </info>");
            } else {
                $output->write($image);
            }
        }
    }
}
