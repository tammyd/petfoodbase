<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Model\PetFood;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePriceCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Retrieve product prices from retailers and update db")
            ->setName('price:update')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, "Id to update", null)
            ->addOption('brand', null, InputOption::VALUE_REQUIRED, "brand to update", null)
            ->addOption('no_search', null, InputOption::VALUE_NONE, "Skip Chewy search")
            ->addOption('min', null, InputOption::VALUE_REQUIRED, "Min Id to use; skip others", 0);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $catfoodService = $this->container->get('catfood');

        $skipSearch = $input->getOption('no_search');

        $results = [];
        if ($input->getOption('id')) {
            $product = $catfoodService->getById($input->getOption('id'));


            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("Looking up prices for Id #" . $input->getOption('id'). " <info>" . $product->getDisplayName() . "</info>");
            }

            $result = $this->updateCatFood($product, $skipSearch);
            $results[] = $result;
            dump($result);
        } elseif ($input->getOption('brand')) {
            $brand = $input->getOption('brand');
            $products = $catfoodService->getByBrand($brand);
            $minId = intval($input->getOption('min'));
            foreach ($products as $product) {
                if ($product->getId() < $minId) {
                    continue;
                }
                if ($product->getDiscontinued()) {
                    continue;
                }
                $result = $this->updateCatFood($product, $skipSearch);
                $results[] = $result;
                dump($result);
            }
        }


    }

    protected function updateCatFood(PetFood $product, $skipSearch = false) {
        $priceLookup = $this->container->get('price.lookup');

        $data = $priceLookup->lookupPrice($product, $skipSearch);

        if ($data) {
            $priceService = $this->container->get('price.service');
            $priceService->updatePrice($product->getId(), $data);
            if ($data['productUrl']) {
                $this->insertChewyUrl($product->getId(), $data['productUrl']);
            }
        }

        return $data;

    }

    protected function insertChewyUrl($id, $url, $overwrite = false) {
        $shopService = $this->container->get('shop.service');
        $all = $shopService->getAll($id);
        if (!isset($all['chewy']) || $overwrite) {
            $shopService->updateChewy($id, $url);
        }
    }

}


