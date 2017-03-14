<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure I can see an Amazon buy button');
$I->lookForwardTo('Ensure I can see an Amazon buy button on product pages');

$urls = [
    "/product/hill's%20ideal%20balance/Baked+Tuna+Recipe"
];

foreach ($urls as $url) {
    $I->amOnPage($url);
    $I->see('Check Price On Amazon');

}
