<?php


namespace PetFoodDB\Controller\Admin;


use PetFoodDB\Controller\Admin\AdminController;
use PetFoodDB\Traits\MathTrait;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;

class AdminChartsController extends AdminController
{

    use MathTrait;
    
    public function chartHomeAction() {
        $this->validateCredentials();
        $this->render('admin/charts.html.twig', []);
    }

    public function chartBreakdownAction() {

        $this->validateCredentials();
        /* @var \PetFoodDB\Service\PetFoodService $catfoodService */
        $catfoodService = $this->get('catfood');
        $products = $catfoodService->getAll();
        $proteinData = [];
        $carbData = [];
        
        $dryProteinData = [];
        $wetProteinData = [];

        $dryCarbData = [];
        $wetCarbData = [];
        foreach ($products as $product) {
            $dry = $product->getPercentages()['dry'];
            $proteinRow = [sprintf("[%d] %s", $product->getId(), $product->getDisplayName()), $dry['protein']];
            $carbRow = [sprintf("[%d] %s", $product->getId(), $product->getDisplayName()), $dry['carbohydrates']];
            $proteinData[] = $proteinRow;
            $carbData[] = $carbRow;
            
            if ($product->getIsWetFood) {
                $wetCarbData[] = $carbRow;
                $wetProteinData[] = $proteinRow;
            } else {
                $dryCarbData[] = $carbRow;
                $dryProteinData[] = $proteinRow;
            }
        }

        array_unshift($proteinData, ['Product', 'Dry Protein %']);
        array_unshift($wetProteinData, ['Product', 'Dry Protein %']);
        array_unshift($dryProteinData, ['Product', 'Dry Protein %']);
        array_unshift($carbData, ['Product', 'Dry Carbohydrate %']);
        array_unshift($wetCarbData, ['Product', 'Dry Carbohydrate %']);
        array_unshift($dryCarbData, ['Product', 'Dry Carbohydrate %']);

        $proteinStr = json_encode($proteinData);
        $carbStr = json_encode($carbData);


        $dryProteinStr = json_encode($dryProteinData);
        $dryCarbStr = json_encode($dryCarbData);

        $wetProteinStr = json_encode($wetProteinData);
        $wetCarbStr = json_encode($wetCarbData);
        
        $templateData = [
            'proteinJson' => $proteinStr,
            'carbJson' => $carbStr,
            'dryProteinJson' => $dryProteinStr,
            'dryCarbJson' => $dryCarbStr,
            'wetProteinJson' => $wetProteinStr,
            'wetCarbJson' => $wetCarbStr,
            'total' => count($products)
        ];

        $this->render('admin/charts-breakdown.html.twig', $templateData);


    }

    public function brandScoreAction() {
        $this->validateCredentials();
        $catFoodService = $this->get('catfood');
        $brandService = $this->get('brand.analysis');

        $result = $brandService->getAllData();

        $chartData = [
            ["Brand", "Average Score"]
        ];

        foreach ($result as $row) {
            $chartData[] = [ucwords($row['brand']), $row['avg_total_score']];
        }
        $jsonStr = json_encode($chartData);

        $brands = $catFoodService->getBrands();
        $brands = array_combine(array_column($brands, 'brand'), array_column($brands, 'brand'));


        $brandRanks = $brands; //copy for altering
         array_walk($brandRanks, function (&$brand, $key) use ($brandService) {
             $b = $brand;
             $brand = $brandService->rateBrand($brand);
             $brand['brand'] = $b;
        });


        $rankCounts = [
            'overall' => array_count_values(array_column($brandRanks, 'overallRating')),
            'wet' => array_count_values(array_filter(array_column($brandRanks, 'wetRating'))),
            'dry' => array_count_values(array_filter(array_column($brandRanks, 'dryRating')))
            ];
        usort($brandRanks, function($a, $b) {
            if ($a['overallRating'] == $b['overallRating']) return 0;
            return ($a['overallRating'] < $b['overallRating']) ? -1 : 1;
        });


        $templateData = [
            'brandJson' => $jsonStr,
            'raw' => $result,
            'ranks' => $brandRanks
        ];


        $this->render('admin/charts-brands.html.twig', $templateData);
    }


    public function chartScoreAction() {
        $this->validateCredentials();

        $reportService = $this->get('reporting');
        $wetNutritionScores = $reportService->getNutritionScoresByType('wet');
        $wetIngredientScores = $reportService->getIngredientScoresByType('wet');
        $wetScores = $reportService->getScoresByType('wet');

        $dryNutritionScores = $reportService->getNutritionScoresByType('dry');
        $dryIngredientScores = $reportService->getIngredientScoresByType('dry');
        $dryScores = $reportService->getScoresByType('dry');


        $allScores = [];
        foreach ($wetScores as $wetScore) {
            $score = $wetScore['score'];
            $allScores[$score] = $wetScore;
            $allScores[$score]['type'] = 'all';
        }
        foreach ($dryScores as $dryScore) {
            $score = $dryScore['score'];
            if (!isset($allScores[$score])) {
                $allScores[$score] = $dryScore;
                $allScores[$score]['type'] = 'all';
            } else {
                $allScores[$score]['count'] += $dryScore['count'];
                $allScores[$score]['type_count'] += $dryScore['type_count'];
            }
        }
        $allScores = array_values($allScores);
        

        $dnsc = "[Dry] Nutrition Score Chart";
        $this->scoreLineChart($dryNutritionScores, $dnsc, "Nutrition Score", "Count");

        $disc = "[Dry] Ingredient Score Chart";
        $this->scoreLineChart($dryIngredientScores, $disc, "Ingredient Score", "Count");

        $dsc = "[Dry] Total Score Chart";
        $this->scoreLineChart($dryScores, $dsc, "Total Score", "Count", 'score');


        $wnsc = "[Wet] Nutrition Score Chart";
        $this->scoreLineChart($wetNutritionScores, $wnsc, "Nutrition Score", "Count");

        $wisc = "[Wet] Ingredient Score Chart";
        $this->scoreLineChart($wetIngredientScores, $wisc, "Ingredient Score", "Count");

        $wsc = "[Wet] Total Score Chart";
        $this->scoreLineChart($wetScores, $wsc, "Total Score", "Count", 'score');

        $all = "Total Score Chart";
        $this->scoreLineChart($allScores, $all, "Total Score", "Count", 'score');


        
        $data = [
            'wet_nutrition_score_chart' => $wnsc,
            'wet_ingredient_score_chart' => $wisc,
            'wet_score_chart' => $wsc,
            'dry_nutrition_score_chart' => $dnsc,
            'dry_ingredient_score_chart' => $disc,
            'dry_score_chart' => $dsc,
            'all_score_chart' => $all
        ];

        $this->render('admin/charts-score.html.twig', $data);

    }

    protected function scoreTableChart($rawData, $title) {
        $lava = $this->get('lava');
        $data = $lava->DataTable();
        $data->addStringColumn("Rating")
            ->addNumberColumn("Count");
        foreach ($rawData as $row) {
            $chartRow = [$row['rating'], $row['count']];
            $data->addRow($chartRow);
        }

        $lava->TableChart($title, $data, [
            'title' => $title
        ]);

        return $title;
    }

    protected function scoreLineChart($rawData, $title, $xTitle, $yTitle, $scoreField='rating') {
        $lava = $this->get('lava');
        $data = $lava->DataTable();
        $data->addNumberColumn("Score")
            ->addNumberColumn("Count");
        foreach ($rawData as $row) {
            $chartRow = [$row[$scoreField], $row['count']];
            $data->addRow($chartRow);
        }

        $lava->LineChart($title, $data, [
            'title' => $title,
            'hAxis' => ['title'=>$xTitle],
            'vAxis' => ['title'=>$yTitle],
            'legend' => ['position' => 'bottom']
        ]);

        return $title;
    }

    protected function setupChart() {
        $lava = $this->get('lava');

        $data = $lava->DataTable();
        $data->addStringColumn('Nutrient')
            ->addNumberColumn('Value');

        $data->addRow(['Protein', 40]);
        $data->addRow(['Carbs', 30]);
        $data->addRow(['Fat', 25]);
        $data->addRow(['Other', 5]);


        $lava->TableChart('DMB', $data, [
            'title' => 'Dry Matter Breakdown'
            ]);

        return 'DMB';

    }


}
