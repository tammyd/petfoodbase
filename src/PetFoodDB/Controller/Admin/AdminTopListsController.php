<?php


namespace PetFoodDB\Controller\Admin;


class AdminTopListsController extends AdminController
{
    public function topHomeAction() {
        $this->validateCredentials();
        $this->render('admin/top.html.twig');
    }

    protected function getAllProducts() {
        $catfoodService = $this->get('catfood');
        $products = $catfoodService->getAll();
        $ingredientAnalysisService = $this->get('ingredient.analysis');
        $controller = $this->get('product.controller');

        foreach ($products as $product) {

            $product = $controller->getAllProductDetails($product);

            $id = $product->getId();
            $analysis = $catfoodService->getDb()->analysis[$id];
            $nut = $analysis['nutrition_rating'];
            $ing = $analysis['ingredients_rating'];
            $score = $nut + $ing;


            $product->addExtraData('nutrition_rating', $nut);
            $product->addExtraData('ingredients_rating', $ing);
            $product->addExtraData('score', $score);

            $product->addExtraData('top_ingredients', $ingredientAnalysisService::getFirstIngredients($product, 5));

            $product->update(['asin'=>null]);
            $products[$product->getId()] = $product;
        }

        return $products;
    }

    public function sortByDiff($productA, $productB) {

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

    public function topWetAction() {

        $this->validateCredentials();
        $ranker = $this->get('catfood.ranker');
        $products = $ranker->getTopWet(8);


        $data = [
            'products' => $products,
            'title' => "Top Wet Products",
            'info' => "Top wet products. Sorted by score, the diff (protein - carbs)"
        ];

        $this->render('admin/admin-product-list.html.twig', $data);
    }


    public function topDryAction() {

        $this->validateCredentials();
        $ranker = $this->get('catfood.ranker');
        $products = $ranker->getTopDry();


        $data = [
            'products' => $products,
            'title' => "Top Dry Products",
            'info' => "Top dry products. Sorted by score, the diff (protein - carbs)"
        ];
        $this->render('admin/admin-product-list.html.twig', $data);
    }

    public function topBrandsAction() {
        $this->validateCredentials();
        $brandService = $this->get('brand.analysis');

        $data = $brandService->getAllData();
        $data = array_filter($data, function($row) {
            $discontinued = $this->get('brand.analysis')->isDiscontinued($row['brand']);
            return !$discontinued;
        });

        //sort on brand score
        usort($data, function($a, $b) {
            $aScore = $a['rank'];
            $bScore = $b['rank'];
            return ($aScore < $bScore) ? -1 : 1;
        });

        $displayData = [];
        foreach ($data as $i=>$row) {
            $lastUpdated = $brandService->getLastUpdated($row['brand']);
            $entry = [
                'rank' => $row['rank'],
                'brand' => $row['brand'],
                'score' => $row['avg_total_score'],
                'wet' => $row['num_wet'] ? $row['num_wet']  : 0 ,
                'dry' => $row['num_dry'] ? $row['num_dry']: 0,
                'updated' => $lastUpdated
            ];
            $displayData[] = $entry;
        }

        $renderData = [
            'data' => $displayData,
            'title' => "Top Brands By Score"
        ];


        $this->render('admin/rank-brands.html.twig', $renderData);

    }

    public function topWetBrandsAction() {
        $this->validateCredentials();
        $brandService = $this->get('brand.analysis');

        $data = $brandService->getAllData();

        $data = array_filter($data, function($x) {
            if (!$x['num_wet']) {
                return false;
            }
            return true;
        });

        usort($data, function($a, $b) {
            $aScore = $a['wet_rank'];
            $bScore = $b['wet_rank'];
            return ($aScore < $bScore) ? -1 : 1;
        });


        $displayData = [];
        foreach ($data as $i=>$row) {
            $entry = [
                'rank' => $row['wet_rank'],
                'brand' => $row['brand'],
                'score' => $row['wet_avg_total_score']
            ];
            $displayData[] = $entry;
        }

        $renderData = [
            'data' => $displayData,
            'title' => "Top Wet Brands By Score"
        ];

        $this->render('admin/rank-brands.html.twig', $renderData);
    }

    public function topDryBrandsAction() {
        $this->validateCredentials();
        $brandService = $this->get('brand.analysis');

        $data = $brandService->getAllData();

        $data = array_filter($data, function($x) {
            if (!$x['num_dry']) {
                return false;
            }
            return true;
        });

        usort($data, function($a, $b) {
            $aScore = $a['dry_rank'];
            $bScore = $b['dry_rank'];
            return ($aScore < $bScore) ? -1 : 1;
        });

        $displayData = [];
        foreach ($data as $i=>$row) {
            $entry = [
                'rank' => $row['dry_rank'],
                'brand' => $row['brand'],
                'score' => $row['dry_avg_total_score']
            ];
            $displayData[] = $entry;
        }

        $renderData = [
            'data' => $displayData,
            'title' => "Top Dry Brands By Score"
        ];

        $this->render('admin/rank-brands.html.twig', $renderData);
    }

}
