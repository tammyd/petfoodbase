<?php


namespace PetFoodDB\Blog;


use PetFoodDB\Command\Tools\SearchToolCommand;

class BestWetCatFoodProducts extends CustomBlogPostData
{
    public function postData()
    {
        $catFoodService = $this->container->get('catfood');
        $rankerService = $this->container->get('catfood.ranker');
        $ingredientAnalysisService = $this->container->get('ingredient.analysis');
        $charts = $this->container->get('charts.service');


        //values are taken from /admin/lists/wet and similar products grouped together

        $productInfo = [];
        $productIds = [
            1 => [2074, [2076, 2075]], //only natural pet
            2 => [2292, [2290, 2289, 2288, 2285, 2284, 2287, 2286]], //boreal
            3 => [864, []], //petcurean go
            4 => [1228, [1234, 1235, 1230, 1224, 1232, 1227]], //tiki cat
            5 => [1426, []], //earthborn holistic
            6 => [1754, [1755, 1753, 1752]], //against the grain
            7 => [1896, [1897]], //daves
            8 => [839, [830, 835, 836, 831]], //nutro
            9 => [1683, [1682]], //soulistic
            10 => [2203, []], //nature's logic
            11 => [1065, [1067]], //wellness core
            12 => [1330, []], //solid gold
            13 => [812, [817, 821, 814, 819, 818, 815, 816]], //nutro perfect portions
            14 => [1859, [1857]], //bravo
            15 => [1559, [1560, 1558]], //ziwipeak
            16 => [1927, [1929, 1926, 1925, 1924, 1928]], //grandma maes
            17 => [295,[290]], //first mate
            18 => [1998,[1997]], //holistic select

        ];

        $rankedProducts = [];

        foreach ($productIds as $rank=>$idData) {
            $id = $idData[0];
            $relatedIds = $idData[1];

            /* @var \PetFoodDB\Model\CatFood */
            $product = $catFoodService->getById($id);

            $product->addExtraData('primaryProteins',$ingredientAnalysisService->getPrimaryProteins($product));

            $calorieChart = $charts->getCalorieChart($product);
            $product->addExtraData('calorie-chart', $calorieChart);

            $productData = [
                'rank' => $rank,
                'product' => $rankerService->getAllProductData($product),
                'related' => []
            ];


            $ingredientAnalysisService->getPrimaryProteins($product);

            foreach ($relatedIds as $id) {
                $productData['related'][] = $catFoodService->getById($id); //dont need full info here
            }

            $rankedProducts[$rank] = $productData;
        }

        //krsort($rankedProducts);

        return ['rankedProducts' => $rankedProducts];

    }

    public function addSimilar(&$product, $similarIds) {
        $catFoodService = $this->container->get('catfood');
        $similar = [];
        foreach ($similarIds as $otherId) {
            $similar[] = $catFoodService->getById($otherId);
        }
        $product->addExtraData('similar', $similar);
    }


}
