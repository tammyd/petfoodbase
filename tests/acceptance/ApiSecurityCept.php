<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('make sure the api is restricted');

$I->sendGET('/data/catfood/-broth?brands=');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::BAD_REQUEST); // 200