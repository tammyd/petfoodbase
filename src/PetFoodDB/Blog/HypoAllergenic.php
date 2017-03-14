<?php

namespace PetFoodDB\Blog;

use PetFoodDB\Command\Tools\SearchToolCommand;

class HypoAllergenic extends CustomBlogPostData
{

    public function postData() {

        $catFoodService = $this->container->get('catfood');
        $ingredientAnalysisService = $this->container->get('ingredient.analysis');
        $products = SearchToolCommand::getHypoallegenicFoods($catFoodService, $ingredientAnalysisService);
        $controller = $this->container->get('product.controller');

        //ran `console.php db:task hypo` to get the data
        //dont want this dynamic in case new foods are added to db
        //            15    14    13    12    11    10    9     8     7     6     5     4     3     2     1
        $filterIds = [1633, 1518, 1162, 1169, 1493, 1167, 1488, 1489, 1490, 1491, 1510, 1512, 1160, 1572, 1573];
        

        $products = array_filter($products, function($p) use ($filterIds) {
            $id = $p->getId();
            return in_array($id, $filterIds);
        });
        

        foreach ($products as $i=>$product) {
            unset($products[$i]);
            $products[$product->getId()] = $controller->getAllProductDetails($product);

            $product->addExtraData('top_ingredients', $ingredientAnalysisService::getFirstIngredients($product, 5));
        }
        
        $products[1633]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/15-lotus-pork.jpg");
        //$products[1518]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/14-wild-calling-ally.jpg");
        $products[1162]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/13-natures-variety-lid-duck.jpg");
        $products[1169]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/12-natures-variety-duck.jpg");
        $products[1493]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/11-addiction-brushtail.jpg");
        $products[1167]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/10-natures-variety-venison.jpg");
        $products[1488]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/9-addiction-rabbit.jpg");
        $products[1489]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/8-addiction-buffalo.jpg");
        $products[1490]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/7-addiction-duck.jpg");
        $products[1491]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/6-addiction-vension.jpg");
        $products[1510]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/5-wild-calling-buffalo.jpg");
        $products[1512]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/4-wild-calling-rabbit.jpg");
        $products[1160]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/3-natures-variety-lid-rabbit.jpg");
        $products[1572]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/2-wysong-duck.jpg");
        $products[1573]->addExtraData('list_img', "/img/blog/hypoallergenic-cat-food/1-wysong-rabbit.jpg");



        

        return ['products' => $products];
    }

}
