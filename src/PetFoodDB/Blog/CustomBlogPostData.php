<?php
namespace PetFoodDB\Blog;



use PetFoodDB\Command\Tools\SearchToolCommand;

abstract class CustomBlogPostData
{
    protected $container;
    protected $config;
    protected $postMetaData = [];
    protected $post = "";

    public function __construct(array $config, $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    abstract function postData();

    public function setPost($post) {
        $this->post = $post;

        return $this;
    }

    public function setPostMetaData(array $meta) {
        $this->postMetaData = $meta;

        return $this;
    }

    public function getPostMetaData() {
        return $this->postMetaData;
    }

    protected function getProductDataForIds(array $ids) {

        $catFoodService = $this->container->get('catfood');
        $ingredientAnalysisService = $this->container->get('ingredient.analysis');
        $controller = $this->container->get('product.controller');

        $products = [];
        $initProducts = [];
        foreach ($ids as $id) {
            $initProducts[$id] = $catFoodService->getById($id);
        }

        foreach ($initProducts as $i=>$product) {
            $product = $controller->getAllProductDetails($product);
            $product->addExtraData('top_ingredients', $ingredientAnalysisService::getFirstIngredients($product, 5));

            $products[$product->getId()] = $product;
        }

        return $products;

    }
    
}
