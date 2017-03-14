<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure I can see an Chewy buy button');
$I->lookForwardTo('Ensure I can see an Chewy buy button on product pages');

$urls = [
    "/product/hill's%20ideal%20balance/Baked+Tuna+Recipe"
];

foreach ($urls as $url) {
    $I->amOnPage($url);
    $I->see('Get 20% off at chewy.com');

}
