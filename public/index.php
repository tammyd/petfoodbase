<?php
ini_set('session.use_cookies', 1); //testing this...
session_cache_limiter(false); //fuck this shit -- needed to override caching headers.
session_start();
require '../includes/autoload.php'; //autoload our classes

// dependency injection container
$app = require '../includes/services.php';

// setup env variables

error_reporting($config['php.error_reporting']);
ini_set('display_errors', $config['php.display_errors']);
ini_set('log_errors', $config['php.log_errors']);
ini_set('error_log', $config['php.error_log']);
date_default_timezone_set($config['php.date.timezone']);

session_cache_limiter(false); //fuck this shit -- needed to override caching headers.

// load routes and run application
foreach (glob($config['path.routes'] . '*php') as $file) {
    require_once $file;
}

// Run app
$app->run();
