<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BulkKeywordCommand extends ContainerAwareCommand
{

    use StringHelperTrait;

    //keyword, tld, datacenter,device,locale,custom
    protected function configure()
    {
        $this
            ->setDescription("Bulk keyword import into serphacker")
            ->setName('seo:bulk')
            ->addOption("tld", null, InputOption::VALUE_OPTIONAL, null, "com")
            ->addOption("datacenter", null, InputOption::VALUE_OPTIONAL, null, null)
            ->addOption("device", null, InputOption::VALUE_OPTIONAL, null, "desktop")
            ->addOption("locale", null, InputOption::VALUE_OPTIONAL, null, "united states")
            ->addOption("custom", null, InputOption::VALUE_OPTIONAL, null, null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lines = [];
        $catfoodService = $this->container->get('catfood');
        $brands = $catfoodService->getBrands();
        foreach ($brands as $brand) {
            $line = [];
            $brand = $brand['brand'];

            if ($this->startsWith($brand, "the ")) {
                $brand = str_replace("the ", "", $brand);
            }

            if ($this->contains($brand, "™")) {
                $brand = str_replace("™", "", $brand);
            }

            $line[] = $brand . " cat food reviews";
            $line[] = $input->getOption('tld');
            $line[] = $input->getOption('datacenter');
            $line[] = $input->getOption('device');
            $line[] = $input->getOption('locale');
            $line[] = $input->getOption('custom');

            $lines[] = substr(implode(",", $line), 0, -1);
        }

        foreach ($lines as $line) {
            $output->writeln($line);
        }

    }

}
