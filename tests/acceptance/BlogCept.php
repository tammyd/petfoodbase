<?php

//console.php info listBlog (which explains the old skool array formatting
$blogs = array (
    0 =>
        array (
            'url' => '/blog/best-wet-cat-foods',
            'title' => 'CatFoodDB\'s Best Wet Cat Foods',
        ),
    1 =>
        array (
            'url' => '/blog/dry-matter-basis',
            'title' => 'Dry Matter Basis: Comparing Cat Food Effectively',
        ),
    2 =>
        array (
            'url' => '/blog/can-cats-eat-bananas',
            'title' => 'Can Cats Eat Bananas?',
        ),
    3 =>
        array (
            'url' => '/blog/hypoallergenic-cat-food',
            'title' => 'Hypoallergenic Cat Food: <br>14 Top Canned Foods For Cats With Allergies',
        ),
    4 =>
        array (
            'url' => '/blog/3-steps-to-decoding-cat-food-labels',
            'title' => '3 Steps to Decoding Cat Food Labels',
        ),
);

$I = new AcceptanceTester($scenario);
$I->wantTo('Check that my blog pages are up');

$I->amOnPage('/blog');
$I->seeResponseCodeIs(200);
$I->see("CatFoodDB Blog");
foreach ($blogs as $data) {
    $url = $data['url'];
    $title = strip_tags($data['title']);
    $I->click("//a[@href=\"$url\"]");
    $I->seeResponseCodeIs(200);
    $I->see($title);
}