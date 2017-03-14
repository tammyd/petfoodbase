<?php

$app->get('/admin/insert', function () use ($app) {
    $app->container['admin.controller']->getInsertFormAction();
});
//
$app->post('/admin/insert/process', function () use ($app) {
    $app->container['admin.controller']->submitInsertAction();
});

$app->get('/admin/update/:id', function ($id) use ($app) {
    $app->container['admin.controller']->getUpdateFormAction($id);
});

$app->post('/admin/update/process', function () use ($app) {
    $app->container['admin.controller']->submitUpdateAction();
});


$app->get('/admin/info', function () use ($app) {
    $app->container['admin.controller']->phpInfoAction();
});

$app->get('/admin/images', function () use ($app) {
    $app->container['admin.controller']->imagesAction();
});

$app->get('/admin/products', function () use ($app) {
    // Render index view
    $app->container['admin.controller']->listAction();
});

$app->get('/admin/product/:id', function ($id) use ($app) {
    // Render index view
    $app->container['admin.controller']->productAction($id);
});

$app->get('/admin/charts', function () use ($app) {
    // Render index view
    $app->container['admin.charts.controller']->chartHomeAction();
});

$app->get('/admin/charts/products', function () use ($app) {
    // Render index view
    $app->container['admin.charts.controller']->chartScoreAction();
});

$app->get('/admin/charts/brands', function () use ($app) {
    // Render index view
    $app->container['admin.charts.controller']->brandScoreAction();
});

$app->get('/admin/charts/breakdowns', function () use ($app) {
    // Render index view
    $app->container['admin.charts.controller']->chartBreakdownAction();
});


$app->get('/admin/lists', function () use ($app) {
    // Render index view
    $app->container['admin.lists.controller']->topHomeAction();
});
$app->get('/admin/top/wet', function () use ($app) {
    // Render index view
    $app->container['admin.lists.controller']->topWetAction();
});
$app->get('/admin/top/dry', function () use ($app) {
    // Render index view
    $app->container['admin.lists.controller']->topDryAction();
});





