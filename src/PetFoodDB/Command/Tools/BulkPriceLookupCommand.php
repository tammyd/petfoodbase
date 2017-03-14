<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BulkPriceLookupCommand extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setDescription("Lookup the image for a amazon product by ASIN")
            ->setName('amazon:bulk:price')
            ->addArgument('brand', InputArgument::REQUIRED)
            ->addOption("csv", null, InputOption::VALUE_REQUIRED, "output as csv file");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lookup = $this->container->get('amazon.lookup');
        $catfood = $this->container->get('catfood');

        $brand = $input->getArgument('brand');
        if ($brand == 'all') {
            $products = $catfood->getAll();
        } else {
            $products = $catfood->getByBrand($input->getArgument('brand'));
        }

        if (!$products) {
            $output->writeln("<error>No products for brand '$brand'</error>");
            return;
        }

        $rows = [];

        $progress = new ProgressBar($output, count($products));
        foreach ($products as $product) {
            $row = [
                'id' => $product->getId(),
                'brand' => $product->getBrand(),
                'type' => $product->getIsDryFood() ? "Dry" : "Wet",
                'flavor' => $product->getFlavor(),
                'asin' => $product->getAsin()
            ];


            $asin = $product->getAsin();

            if ($asin) {
                $price = $lookup->lookupPrice($asin);
                sleep(1);

                $row['price'] =  $price['amount'];
                $row['size'] = $price['size'];
                $row['title'] = $price['title'];

            }
            $rows[] = $row;
            $progress->advance();

        }
        $progress->finish();
        $output->writeln("");

        $header = array_keys($rows[0]);

        if ($input->getOption('csv')) {
            $this->outputCsv($input->getOption('csv'), $header, $rows);
        } else {
            $this->outputTable($output, $header, $rows);
        }



    }

    protected function outputCsv($filename, $header, $rows) {

        $stream = fopen($filename, 'w');
        fputcsv($stream, $header);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

    }

    protected function outputTable($output, $header, $rows) {
        $table = new Table($output);
        $table
            ->setHeaders($header)
            ->setRows($rows)
        ;
        $table->render();
    }
}

