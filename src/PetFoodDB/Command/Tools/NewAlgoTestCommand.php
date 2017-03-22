<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\MathTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class NewAlgoTestCommand extends ContainerAwareCommand
{
    protected $input;
    protected $output;
    protected $stopwatch;

    use MathTrait;

    protected function configure()
    {
        $this
            ->setDescription("Test")
            ->setName('algo:test', "DEPRECATED")
            ->addOption('id', null, InputOption::VALUE_REQUIRED)
            ->addOption('brand', null, InputOption::VALUE_REQUIRED);
    }

    public function __construct(\Slim\Helper\Set $container, $name = null)
    {
        parent::__construct($container, $name);
        $this->stopwatch = new Stopwatch();
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->timeFtnStart(__FUNCTION__);
        $this->input = $input;
        $this->output = $output;


        $service = $this->container->get('catfood.analysis');

        $data = $service->getData();



        $this->timeFtnStop(__FUNCTION__);
    }

    protected function calcStatsForProduct(PetFood $product, $wetFoodAverages, $dryFoodAverages) {
        if ($product->getIsWetFood()) {
            $averages = $wetFoodAverages;
        } else {
            $averages = $dryFoodAverages;
        }
        $nutrients = [
            'carbohydrates' => 'dry',
            'fibre' => 'dry',
            'fat' => 'dry',
            'protein' => 'dry',
            'other' => 'dry',
            'moisture' => 'wet'

        ];

        $nutrients = [
            'carbohydrates' => 'dry',
            'fibre' => 'dry',
            'fat' => 'dry',
            'protein' => 'dry',
            'other' => 'dry',
            'moisture' => 'wet'

        ];

        $stats = [
            'id' => $product->getId()
        ];

        foreach ($nutrients as $nutrient => $nutrientType) {

            $value = $product->getPercentages()[$nutrientType][$nutrient];
            $sd = $averages[$nutrient]['stddev'];
            $mean = $averages[$nutrient]['mean'];

            $sdCount = $this->stdDevDiff($value, $sd, $mean);
            $stats[$nutrient] = $sdCount;
        }

        $caloriesValue = $product->getCaloriesPer100g()['total'];
        $caloriesMean = $averages['calories']['mean'];
        $caloriesStdDev = $averages['calories']['stddev'];

        $stats['calories'] = $this->stdDevDiff($caloriesValue, $caloriesStdDev, $caloriesMean);

        return $stats;
    }

    protected function stdDevDiff($value, $stddev, $mean) {
        $diff = $value - $mean;

        if ($stddev) {
            $sdCount = $diff / $stddev;
        } else {
            $sdCount = 0;
        }

        return $sdCount;
    }

    protected function timeFtnStart($name) {
        $this->stopwatch->start($name);
    }

    protected function timeFtnStop($name) {
        $event = $this->stopwatch->stop($name);
        $this->comment(sprintf("%s took %d ms</comment>", $name, $event->getDuration()));
    }

    protected function comment($string) {
        $this->output->writeln(sprintf("<comment>[-] %s </comment>", $string));
        return $this;
    }

    protected function info($string) {
        $this->output->writeln(sprintf("<info>[+] %s </info>", $string));
        return $this;
    }

    protected function question($string) {
        $this->output->writeln(sprintf("<question>[?] %s </question>", $string));
        return $this;
    }

    protected function error($string) {
        $this->output->writeln(sprintf("<error>[!] %s </error>", $string));
        return $this;
    }


    protected function calcAverageAverages(array $averages) {

        $keys = ['carbohydrates', 'moisture', 'fat', 'fibre', 'calories', 'other', 'protein'];
        $result = [];
        foreach ($keys as $key) {
            $values = array_column($averages, $key);
            if (count($values)) {
                $mean = $this->mean($values);
                $stddev = $this->standard_deviation($values);
                $keyData = [
                    'mean' => $mean,
                    'stddev' => $stddev,
                    'count' => count($values)
                ];
                $result[$key] = $keyData;
            }
        }

        return $result;

    }

    protected function calcBrandAverages() {

        $this->timeFtnStart(__FUNCTION__);

        /* @var \PetFoodDB\Service\PetFoodService */
        $service = $this->container->catfood;
        $stats = $this->container->stats;
        $brands = $service->getBrands();

        $brandAverages = [];
        foreach ($brands as $brand) {
            $brandName = $brand['name'];
            $this->info(sprintf("Starting %s", $brandName));


            $products = $service->getByBrand($brand['name']);
            $wetProducts = array_filter($products, function($p) {
                return $p->getIsWetFood();
            });
            $dryProducts = array_filter($products, function($p) {
                return $p->getIsDryFood();
            });

            $dryStats = $stats->getStatsForCatfood($dryProducts);
            $wetStats =  $stats->getStatsForCatfood($wetProducts);

            $brandAverages[$brandName]['dry'] = $dryStats;
            $brandAverages[$brandName]['wet'] = $wetStats;

        }


        $this->timeFtnStop(__FUNCTION__);
        return $brandAverages;



    }




}
