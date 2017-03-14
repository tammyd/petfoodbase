<?php

$app->get('/blog/:path', function ($path) use ($app) {
    $app->container['blog.controller']->postAction($path);
});

$app->get('/blog', function () use ($app) {
    $app->container['blog.controller']->blogHomeAction();
});

$app->get('/blog/drafts/:path', function ($path) use ($app) {
    $app->container['blog.controller']->postDraftAction($path);
});
