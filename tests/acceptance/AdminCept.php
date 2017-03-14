<?php

$routes = [
    '/admin/insert',
    '/admin/update/1',
    '/admin/list',
    '/admin/info',
    '/admin/images',
    '/admin/products',
    '/admin/product/1',
    '/admin/charts',
    '/admin/charts/products',
    '/admin/charts/brands',
    '/admin/charts/breakdowns',
    '/admin/lists',
    '/admin/lists/wet',
    '/admin/lists/dry',
];

foreach ($routes as $route) {

    $I = new AcceptanceTester($scenario);
    $I->wantTo('Check my admin pages are not accidently accessible');
    $I->amOnPage($route);
    $I->seeResponseCodeIs(404);
}

