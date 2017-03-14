<?php

namespace PetFoodDB\Command\Tools;

use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Controller\ProductPageTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BrandRatingCommand extends TableCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Rate the brands")
            ->setName('brand:ratings')
            ->addOption('csv')
            ->addOption('update');
            
    }

    protected function formatValue($value) {
        return $value ? sprintf("%0.02f", $value) : "----";
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->container->get('brand.analysis');
        $catfoodService = $this->container->get('catfood');
        $brands = $catfoodService->getBrands();
        $progressBar = new ProgressBar($output, count($brands));

        if ($input->getOption('update')) {
            $service->updateDB([$progressBar, 'advance']);
            
        } else {

            $result = $service->calculateBrandAnalysis([$progressBar, 'advance']);
            $displayData = $result['data'];
            $headers = $result['headers'];
            $progressBar->finish();

            if ($input->getOption('csv')) {
                array_unshift($displayData, $headers);
                $this->outputCSV($displayData);


            } else {
                $this->displayTable($output, $displayData, "Brand Averages", $headers);
            }
        }



    }

    protected function sortDryRows($rowA, $rowB) {
        $scoreA = $rowA['dry_avg_total_score'];
        $scoreB = $rowB['dry_avg_total_score'];

        return ($scoreA < $scoreB) ? 1 : -1;
    }
    protected function sortWetRows($rowA, $rowB) {
        $scoreA = $rowA['wet_avg_total_score'];
        $scoreB = $rowB['wet_avg_total_score'];

        return ($scoreA < $scoreB) ? 1 : -1;
    }

    protected function addNumbering(array $data) {
        $newData = [];
        foreach ($data as $i=>$row) {
            $newRow = array_merge(['id' => $i+1], $row);
            $newData[] = $newRow;
        }

        return $newData;
    }


    protected function displayTable($output, $rows, $title, $headers = []) {
        $table = new Table($output);

        if (!$headers) {
            $headers = array_keys($rows[0]);
        }

        $table->setHeaders(array(
            [new TableCell($title, ['colspan' => count($headers)])],
            $headers
        ));
        $table->setRows($rows);
        foreach ($headers as $i=>$header) {
            if ($i >= 1) {
                $table->setColumnStyle($i, $this->rightAligned());
            }
        }

        $table->render();
    }


}
