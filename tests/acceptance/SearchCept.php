<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('check the search api is up');

$I->haveHttpHeader('X-CatfoodDB-Admin', 'DB2CB9B9-CB8E-4B11-A8B7-1A17ED09CBEA');
$I->sendGET('/data/catfood/-broth?brands=');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'count' => 'integer:>0',
    'type' => 'string',
]);