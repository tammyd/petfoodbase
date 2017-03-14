<?php

if ($config['app.coming_soon']) {
    $app->get('/.*', function() use ($app)  {
        $app->render('coming_soon.html.twig');
    });
} else {

    // Define routes
    $app->get('/', function () use ($app) {
        // Render index view
        $app->container['page.controller']->homeAction();
    });
    $app->get('/search', function () use ($app) {
        // Render index view
        $app->container['page.controller']->searchAction();
    });
    $app->get('/faq', function () use ($app) {
        // Render index view
        $app->container['page.controller']->faqAction();
    });
    $app->get('/about', function () use ($app) {
        // Render index view
        $app->container['page.controller']->aboutAction();
    });
    $app->get('/resources', function () use ($app) {
        // Render index view
        $app->container['page.controller']->resourcesAction();
    });

    $app->get('/brand/:name', function ($name) use ($app) {
        // Render brand page
        $app->container['page.controller']->brandAction($name);
    });

    $app->get('/product/:id', function ($id) use ($app) {
        // Render product page
        $app->container['product.controller']->productIdAction($id);
    });
    
    $app->get('/product/:brand/:slug', function ($brand, $slug) use ($app) {
        // Render product page
        $app->container['product.controller']->productAction($brand, $slug);
    });

    $app->get('/sitemap', function () use ($app) {
        $app->container['page.controller']->sitemapAction();
    });
}


// Define 404 template
$app->notFound(function () use ($app) {
    $app->container['page.controller']->fourOrFourAction();
});

