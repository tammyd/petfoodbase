<?php

$app->get('/data/catfood/stats', function () use ($app) {
    $app->container['catfood.controller']->statsAction();
});

$app->get('/data/catfood/brands', function () use ($app) {
    $app->container['catfood.controller']->brandsAction();
});

$app->get('/data/catfood/:id', function ($id) use ($app) {
    $app->container['catfood.controller']->getByIdAction($id);
})->conditions(['id'=>'\d+']);

$app->get('/data/catfood(/:search)', function ($search="") use ($app) {
    $app->container['catfood.controller']->searchAction($search);
})->conditions(['search'=>'.*']);