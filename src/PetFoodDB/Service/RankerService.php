<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;

class RankerService
{
    protected $catFoodService;
    protected $catFoodAnalysisService;
    protected $catFoodAnalysisWrapper;
    protected $ingredientAnalysis;
    protected $amazonTemplate;

    protected $allProducts;


    public function __construct(PetFoodService $catFoodService,
                                NewAnalysisService $catFoodAnalysisService,
                                AnalysisWrapper $catFoodAnalysisWrapper,
                                AnalyzeIngredients $ingredientAnalysis,
                                $amazonTemplate)
    {
        $this->catFoodService = $catFoodService;
        $this->catFoodAnalysisService = $catFoodAnalysisService;
        $this->catFoodAnalysisWrapper = $catFoodAnalysisWrapper;
        $this->ingredientAnalysis = $ingredientAnalysis;
        $this->amazonTemplate = $amazonTemplate;

    }

    public function getAllProductData(PetFood $product) {

        $product = $this->catFoodService->updateExtendedProductDetails(
            $product,
            $this->amazonTemplate,
            $this->catFoodAnalysisService,
            $this->catFoodAnalysisWrapper);

        $id = $product->getId();
        $analysis = $this->catFoodService->getDb()->analysis[$id];
        $nut = $analysis['nutrition_rating'];
        $ing = $analysis['ingredients_rating'];
        $score = $nut + $ing;

        $product->addExtraData('nutrition_rating', $nut);
        $product->addExtraData('ingredients_rating', $ing);
        $product->addExtraData('score', $score);

        $product->addExtraData('top_ingredients', $this->ingredientAnalysis->getFirstIngredients($product, 5));

        return $product;
    }

    public function getAllProductWithData() {

        static $allProducts;

        if (!is_null($allProducts)) {
            return $allProducts;
        }


        $products = $this->catFoodService->getAll();
        $products = array_map(function($product) {
            return $this->getAllProductData($product);
        }, $products);

        $allProducts = $products;
        return $products;
    }

    protected function usortByDiff(PetFood $productA, PetFood $productB) {

        $percentagesA = $productA->getPercentages();
        $percentagesB = $productB->getPercentages();
        $proteinA = round($percentagesA['dry']['protein'], 1);
        $proteinB = round($percentagesB['dry']['protein'], 1);
        $carbA = round($percentagesA['dry']['carbohydrates'], 1);
        $carbB = round($percentagesB['dry']['carbohydrates'], 1);

        $diffA = $proteinA  - $carbA;
        $diffB = $proteinB - $carbB;

        if ($diffA == $diffB) {
            return 0;
        }


        return ($diffA < $diffB) ? 1 : -1;

    }

    protected function usortProduct(PetFood $productA, PetFood $productB) {

        $scoreA = $productA->getExtraData('score');
        $scoreB = $productB->getExtraData('score');


        if ($scoreA == $scoreB) {
            return $this->usortByDiff($productA, $productB);
        }
        return ($scoreA < $scoreB) ? 1 : -1;

    }



    public function getTopWet($minScore = 9) {
        $products = $this->getAllProductWithData();
        $products = array_filter($products, function(PetFood $product) use ($minScore) {
            $score = $product->getExtraData('score');
            if ($product->getIsDryFood()) {
                return false;
            }
            return $score >= $minScore;
        });


        usort($products, [$this, 'usortProduct']);

        return $products;
    }

    public function getTopDry($minScore = 8) {
        $products = $this->getAllProductWithData();
        $products = array_filter($products, function(PetFood $product) use ($minScore) {
            $score = $product->getExtraData('score');
            if ($product->getIsWetFood()) {
                return false;
            }
            return $score >= $minScore;
        });


        usort($products, [$this, 'usortProduct']);

        return $products;
    }


}
