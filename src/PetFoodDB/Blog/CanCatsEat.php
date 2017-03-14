<?php


namespace PetFoodDB\Blog;


class CanCatsEat extends CustomBlogPostData
{

    public function postData() {

        $products = $this->getProducts();
        $meta =  $this->getPostMetaData();
        $data = array_merge($meta, ['products'=>$products]);
        
        return $data;
    
    }

    protected function getProducts() {
        $products = [];

        $ids = [864, 865];
        foreach ($ids as $id) {
            $products[$id] = $this->getAllProductDetails($id);
        }

        return $products;
    }

    protected function getAllProductDetails($productId) {
        $service = $this->container->get('catfood');
        $controller = $this->container->get('product.controller');

        $product = $service->getById($productId);
        $product = $controller->getAllProductDetails($product);

        return $product;
    }

}
