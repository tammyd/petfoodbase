<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure that home page loads');
$I->amOnPage('/');
$I->seeResponseCodeIs(200);
$I->see('CatFoodDB');