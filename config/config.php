<?php
$basedir = __DIR__ . '/../';

$env = getenv('APP_ENV');
switch($env) {
    case 'dev':
        $envConfig = require('dev.php'); break;
    case 'stage':
        $envConfig = require('staging.php'); break;
    default:
        $envConfig = require('production.php'); break;
}



$config = [
// Environment
    'php.error_reporting' => E_ALL,
    'php.display_errors'  => true,
    'php.log_errors'      => true,
    'php.error_log'       => $basedir . 'logs/app.log',
    'php.date.timezone'   => 'America/Vancouver',

// SQLite
    'db.dsn'              => 'sqlite:' . $basedir . 'db/brands/MASTER.sqlite',

// MySQL
    /*
        'db.dsn'              => 'mysql:host=localhost;dbname=test',
        'db.username'         => 'dbuser',
        'db.password'         => 'dbpass',
    */

//

// Application paths
    'path.routes'         => $basedir . 'routes/',
    'path.templates'      => $basedir . 'templates/',
    'path.templates.cache'=> $basedir . 'templates/cache/',
    'path.blog'           => $basedir . 'blog',

// App config
    'app.session.secret' => 'purpletetrisblock',
    
    'app.db.dir'        => $basedir . 'db/brands/',

    'amazon.image.url.template' => 'https://images-na.ssl-images-amazon.com/images/I/%s._SL%d_.jpg',
    'amazon.purchase.url.template' => 'http://www.amazon.com/exec/obidos/ASIN/%s/catfood00b-20',

    'amazon.aceess.key' => 'AKIAI3JU5QKXRMV7YPSQ',
    'amazon.secret.key' => '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',
    'amazon.associate.key' => 'catfood00b-20',
    
    'product.path' => "/product/%s",
    'product.dryPlaceholder' => '/img/icons/dryfood2_white.png',
    'product.wetPlaceholder' => '/img/icons/wetfood2_white.png',

    'db.stats.file' => $basedir . 'db/stats/stats.yml',
    'db.brands.file' => $basedir . 'db/stats/brands.yml',

    'admin.header' => 'X-CatfoodDB-Admin',
    'admin.header.value' => 'DB2CB9B9-CB8E-4B11-A8B7-1A17ED09CBEA',

    'cache.dir' => '/tmp/catfooddbcache',

    'scrapers.enabled' => false,

    'use.new.nutrition.algo' => true

    
];

$allConfig = array_merge($config, $envConfig);

return $allConfig;