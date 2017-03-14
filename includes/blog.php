<?php

$app->container->singleton('blog.hypoallergenic', function() use ($app, $config) {
    $service = new \PetFoodDB\Blog\HypoAllergenic($config, $app->container);
    return $service;
});

$app->container->singleton('blog.can-cats-eat', function() use ($app, $config) {
    $service = new \PetFoodDB\Blog\CanCatsEat($config, $app->container);
    return $service;
});

$app->container->singleton('blog.best-wet-cat-food', function() use ($app, $config) {
    $service = new \PetFoodDB\Blog\BestWetCatFoodProducts($config, $app->container);
    return $service;
});
