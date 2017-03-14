<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Check that my other pages are up');

$I->amOnPage('/resources');
$I->seeResponseCodeIs(200);
$I->canSee("Other Resources & Recommendations");

$I->amOnPage('/faq');
$I->seeResponseCodeIs(200);
$I->see('CatFoodDB FAQ');

$I->amOnPage('/about');
$I->seeResponseCodeIs(200);
$I->see('The Origins of CatFoodDB');


