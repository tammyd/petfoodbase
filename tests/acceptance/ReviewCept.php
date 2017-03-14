<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Check a review and ensure it loads');

$I->amOnPage('/product/fancy%20feast/Shredded+White+Meat+Chicken+Fare+With+Garden+Greens+In+A+Savory+Broth');
$I->seeResponseCodeIs(200);
$I->see("Fancy Feast Shredded White Meat Chicken");
$I->see("Nutritional Analysis");
