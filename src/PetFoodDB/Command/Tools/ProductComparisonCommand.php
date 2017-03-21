<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Service\AnalysisWrapper;
use PetFoodDB\Twig\CatFoodExtension;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductComparisonCommand extends TableCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Report on the db")
            ->setName('products:compare')
            ->addOption('csv')
            ->addOption('brand', 'b', InputOption::VALUE_REQUIRED, "Restrict output to brand")
            ->addOption('dry', 'd', InputOption::VALUE_NONE, "dry products only")
            ->addOption('wet', 'w', InputOption::VALUE_NONE, "Wet products only")
            ->addOption('url', 'u', InputOption::VALUE_NONE, "Show product url")
            ->addOption('sort', 's', InputOption::VALUE_REQUIRED, "Column to sort by")
            ->addOption('asc', null, InputOption::VALUE_NONE, "sort asc")
            ->addOption('max', null, InputOption::VALUE_REQUIRED, "max number of records to display");


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        /* @var \PetFoodDB\Service\CatFoodService $catfoodService */
        $catfoodService = $this->container->get('catfood');

        /* @var AnalysisWrapper $analysisWrapper */
        $analysisWrapper = $this->container->get('analysis.access');

        $brands = $this->getBrandsToAnalyze();

        $rows = [];

        foreach ($brands as $brand) {
            $data = $catfoodService->getByBrand($brand);

            if ($input->getOption('dry')) {
                $data = array_filter($data, function ($product) {
                    return $product->getIsDryFood();
                });
            }
            if ($input->getOption('wet')) {
                $data = array_filter($data, function ($product) {
                    return $product->getIsWetFood();
                });
            }

            /* @var PetFood $product */
            foreach ($data as $product) {
                $dry = $product->getPercentages()['dry'];
                $wet = $product->getPercentages()['wet'];
                $analysis = $analysisWrapper->getProductAnalysis($product);

                $row = [];
                $row['id'] = $product->getId();
                $row['carbs'] = $this->formatValue($dry['carbohydrates']);
                $row['protein'] = $this->formatValue($dry['protein']);
                $row['type']  = $product->getIsDryFood() ? "dry" : "wet";
                $row['brand'] = $brand;
                $row['score'] = $analysis['ingredients_rating'] + $analysis['nutrition_rating'];

                $row['flavor'] = $input->getOption('csv') ? $product->getFlavor() : $this->truncateText($product->getFlavor());
                $row['guargum'] = $catfoodService->productContainsIngredient($product, 'guar gum') ? "Y" : "N";
                $row['carrageenan'] = $catfoodService->productContainsIngredient($product, 'carrageenan') ? "Y" : "N";

                if ($input->getOption('url')) {
                    $row['url'] = $this->getUrl($product);
                }

                $rows[] = $row;
            }

        }

        if ($input->getOption('sort')) {
            $sortCol = strtolower($input->getOption('sort'));
            $asc = false;
            if ($input->getOption('asc')) {
                $asc = true;
            }

            $rows = $this->sortData($rows, $sortCol, $asc);
        }

        if ($input->getOption('max') && is_numeric($input->getOption('max')) && $input->getOption('max') > 0) {
            $rows = array_slice($rows, 0, (int)$input->getOption('max'));
        }


        if ($input->getOption('csv')) {
            $headers = $this->getHeaders($rows);
            array_unshift($rows, $headers);
            $this->outputCSV($rows);
        } else {
            $table = $this->getTable($rows);
            $table->render();
        }




    }

    protected function getUrl(PetFood $product) {

        /* @var CatFoodExtension $catfoodExtension */
        $catfoodExtension = $this->container->get('catfood.url');
        $baseUrl = $this->container->config['app.base_url'];
        $catfoodExtension->setBaseUrl($baseUrl);

        $url =  $catfoodExtension->makeAbsoluteUrl($catfoodExtension->catfoodUrl($product));

        //such a hack...
        return str_replace(' ', '%20', $url);



    }

    protected function sortData($rows, $col, $asc = true) {

        usort($rows, function($rowA, $rowB) use ($col, $asc) {
            $a = $rowA[$col];
            $b = $rowB[$col];

            if ($a == $b) {
                return 0;
            }

            if (!$asc) {
                return ($a < $b) ? 1 : -1;
            } else {
                return ($a < $b) ? -1 : 1;
            }
        });


        return $rows;
    }

    protected function truncateText($text, $length=23) {
        $text = substr($text, 0, $length-3) . "...";
        return $text;
    }

    protected function formatValue($num) {;
        $rv = sprintf("%0.02f", $num);

        return $rv;

    }

    protected function getHeaders(array $rows) {
        $headers = [];
        if (isset($rows[0])) {
            $headers = array_map(function ($s) {
                return ucwords($s);
            },
                array_keys($rows[0]));
        }
        return $headers;
    }

    
    protected function getTable(array $rows) {
        $table = new Table($this->output);

        $headers = $this->getHeaders($rows);
        if ($headers) {
            $table->setHeaders($headers);
        }

        if (!isset($rows[1])) {
            return $table;
        }
        $first = $rows[1];

        foreach (array_values($first) as $i=>$value) {
            if (is_numeric($value)) {
                $table->setColumnStyle($i, $this->rightAligned());
            }
        }

        $table->addRows($rows);


        return $table;
    }
    
    protected function getBrandsToAnalyze() {

        /* @var \PetFoodDB\Service\CatFoodService $catfoodService */
        $catfoodService = $this->container->get('catfood');

        if ($this->input->getOption('brand')) {
            $brands = [$this->input->getOption('brand')];
        } else {
            $brands = array_map(function ($brand) {
                return $brand['brand'];
            }, $catfoodService->getBrands());
        }

        return $brands;
        
    }


}
