<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Check the a random set of brand pages are linked to from the home page and load successfully');

$brands = \Helper\Acceptance::getBrandList();

for ($i = 1; $i<10; $i++) {
    $brandI = rand(0, count($brands) - 1);
    $brand = $brands[$i];
    $I->amOnPage('/');
    $I->click($brand);
    $I->seeResponseCodeIs(200);
    $I->see("$brand Cat Food Reviews");
    $I->see("Review Summary");
}