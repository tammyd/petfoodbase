<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\LoggerTrait;
use PetFoodDB\Traits\MathTrait;

class NewAnalysisService
{

    const STAT_ABOVE_AVERAGE_SD = 1.0/2;
    const STAT_SIG_ABOVE_SD = 1;

    use MathTrait;
    use LoggerTrait;

    protected $statsService;
    protected $catfoodService;
    protected $ingredientService;
    protected $useNewAlgo;

    /**
     * NewAnalysisService constructor.
     */
    public function __construct(
        CatFoodService $catfoodService,
        DBStatsService $statsService,
        AnalyzeIngredients $ingredientService,
        $newAlgo = false)
    {

        $this->catfoodService = $catfoodService;
        $this->statsService = $statsService;
        $this->ingredientService = $ingredientService;
        $this->useNewAlgo = $newAlgo;
    }

    public function getIngredientService() {
        return $this->ingredientService;
    }
    

    public function getData(callable $progressFtn = null) {
        $allData = [];

        $brandAverages = $this->calcBrandAverages();

        $wetAverages = [];
        $dryAverages = [];

        //need to seperate into wet and dry
        //actually... just the calories. the rest we'll compare evenlly.
        foreach ($brandAverages as $brand=>$brandData) {



            if ($brandData['wet']['count'] > 0) {
                $wetAverages[$brand] = $brandData['combined']['mean'];

                $wetAverages[$brand]['calories'] = $brandData['wet']['mean']['calories'];

            }
            if ($brandData['dry']['count'] > 0) {
                $dryAverages[$brand] = $brandData['combined']['mean'];

                $dryAverages[$brand]['calories'] = $brandData['dry']['mean']['calories'];

            }


        }

        $wetAverage = $this->calcAverageAverages($wetAverages);
        $dryAverage = $this->calcAverageAverages($dryAverages);

        $numWet = $this->catfoodService->getNumberWetRecords();
        $numDry = $this->catfoodService->getNumberDryRecords();

        //now calculate the # of stddeviations from the mean for a product
        $allCatFoods = $this->catfoodService->getAll();
        foreach ($allCatFoods as $i => $product) {

            $done = (int)(100*$i / count($allCatFoods));
            $doneNext = (int)(100*($i+1) / count($allCatFoods));
            if ($doneNext > $done && !is_null($progressFtn)) {
                $progressFtn($done);
            }


            $stats = $this->calcStatsForProduct($product, $wetAverage, $dryAverage);
            if ($this->useNewAlgo) {
                $nutScore = $this->calcNewNutritionScore($product);
            } else {
                $nutScore = $this->calcNutritionScore($stats);
            }

            $ingScore = $this->ingredientService->calcIngredientScore($product);

            $stats['nutrition_rating'] = $nutScore;
            $stats['ingredients_rating'] = $ingScore;

            if ($product->getIsWetFood()) {
                $stats['type']  = "wet";
                $stats['count'] = $numWet;
            } else {
                $stats['type'] = 'dry';
                $stats['count'] = $numDry;
            }

            $allData[$product->getId()] = $stats;
        }

        return $allData;
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


        /* @var \PetFoodDB\Service\CatFoodService */
        $service = $this->catfoodService;
        $stats = $this->statsService;
        $brands = $service->getBrands();

        $brandAverages = [];
        foreach ($brands as $brand) {
            $brandName = $brand['name'];

            $products = $service->getByBrand($brand['name']);

            //changed my mind to use all products, not seperated wet cs dry
            $wetProducts = array_filter($products, function($p) {
                return $p->getIsWetFood();
            });
            $dryProducts = array_filter($products, function($p) {
                return $p->getIsDryFood();
            });

            $allStats = $stats->getStatsForCatfood($products);
            $dryStats = $stats->getStatsForCatfood($dryProducts);
            $wetStats =  $stats->getStatsForCatfood($wetProducts);

            $brandAverages[$brandName]['dry'] = $dryStats;
            $brandAverages[$brandName]['wet'] = $wetStats;
            $brandAverages[$brandName]['combined'] = $allStats;

        }

        return $brandAverages;

    }


    static function getProductProteinCarbRating(array $stats) {

        $above = 1;
        $below = -1;
        $average = 0;
        $carbs = $stats['carbohydrates'];
        $protein = $stats['protein'];

        if (abs($carbs) < 5 && abs($protein) < 5) {
            //near zero carbs and protein, about average
            return $average;
        } else if ($carbs < 0 && $protein > 0) {
            //above average with both fewer carbs and more protein
            return $above;
        } else if ($carbs > 0 && $protein < 0) {
            //below average with both more carbs and less protein
            return $below;
        } else if ($protein > 0 && $carbs > 0) {
            //some other calculation where the extra protein balances out the high carbs
            $test = $carbs - 2*$protein;
            return $test <= 0 ? $average : $below;

        } else if ($carbs < 0 && $protein < 0) {
            //some other calculation where the low carbs balances out a low protein
            $carbs = abs($carbs);
            $protein = abs($protein);
            $test = $carbs - 2*$protein;

            if ($test > 10) {
                return $above;
            } else if (abs($test) < 2.5) {
                return $average;
            } else {
                return $below;
            }
        } else {
            //some other case?
            return $below;
        }

    }

    public function calcNutritionScore(array $stats) {

        $carbs = $stats['carbohydrates'];
        $protein = $stats['protein'];


        $carbScore = $this->rateCarbs($carbs);
        $proteinScore = $this->rateProtein($protein);

        $this->getLogger()->debug($stats['id'] . " had a carb score of $carbScore and a protein score of $proteinScore");
        //return ceil(($carbScore + $proteinScore)/2);  //round numbers
        return ($carbScore + $proteinScore)/2;  //half numbers

    }

    private function calcNewNutritionScore(PetFood $product) {
        $carbBucketsValues = [5,15,30,42,100];
        $proteinBucketsValues = [35,40,50,60,100];
        $dryValues = $product->getPercentages()['dry'];
        $carbs = $dryValues['carbohydrates'];
        $protein = $dryValues['protein'];
        $carbScore = 0;
        $proteinScore = 0;

        foreach ($carbBucketsValues as $i=>$bucketMax) {
            if ($carbs <= $bucketMax && $carbScore == 0) {
                $carbScore = 5-$i;
                break;
            }
        }

        foreach ($proteinBucketsValues as $i=>$bucketMax) {
            if ($protein <= $bucketMax && $proteinScore == 0) {
                $proteinScore = $i+1;
                break;
            }
        }

        return ceil(($carbScore + $proteinScore)/2);


    }

    private function rateProtein($protein) {

        if ($protein <= -1 * self::STAT_SIG_ABOVE_SD) { //$protein < -1
            return 1;
        } elseif ($protein <= -1 * self::STAT_ABOVE_AVERAGE_SD) { //$protein < .3
            return 2;
        } else if ($protein >= -1*self::STAT_ABOVE_AVERAGE_SD && $protein <= self::STAT_ABOVE_AVERAGE_SD) { //$protein betwtwen -.3 and 0.3
            return 3;
        } else if ($protein >= self::STAT_SIG_ABOVE_SD) { //$protein > 1
            return 5;
        } else if ($protein >= self::STAT_ABOVE_AVERAGE_SD) { //$protein > .3
            return 4; //yes, this is out of order on purpose
        }

    }


    private function rateCarbs($carbs) {

        if ($carbs >= self::STAT_SIG_ABOVE_SD) { //carbs > 1
            return 1;
        } elseif ($carbs >= self::STAT_ABOVE_AVERAGE_SD) { //carbs > .3
            return 2;
        } else if ($carbs >= -1*self::STAT_ABOVE_AVERAGE_SD && $carbs <=self::STAT_ABOVE_AVERAGE_SD) { //carbs betwtwen -.3 and 0.3
            return 3;
        } else if ($carbs <= -1 * self::STAT_SIG_ABOVE_SD) { //carbs < 1
            return 5;
        } else if ($carbs < -1 * self::STAT_ABOVE_AVERAGE_SD) { //carbs < 0.3
            return 4; //yes, this is out of order on purpose
        }

    }

}
