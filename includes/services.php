<?php

use Slim\Views\Twig;

$basedir = __DIR__ . '/../';

$dotenv = new Dotenv\Dotenv($basedir);
$dotenv->load();

$config = require dirname(__FILE__) . '/../config/config.php';

$app = new Slim\Slim(array(
    'view' => new Twig(),
    'templates.path' => $config['path.templates'],
    'debug' => $config['app.debug']
));


$app->add(new Slim\Middleware\SessionCookie());

$app->view(new \PetFoodDB\Twig\ChowderedTwig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => $config['path.templates.cache'],
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);

$catFoodTwigExtension = new \PetFoodDB\Twig\CatFoodExtension();
$catFoodTwigExtension->setProductPath($config['product.path'])
    ->setDryFoodPlaceholderImg($config['product.dryPlaceholder'])
    ->setWetFoodPlaceholderImg($config['product.wetPlaceholder'])
    ->setBaseUrl($config['app.base_url']);

$lava = new \Khill\Lavacharts\Lavacharts();
$app->view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
    new \JSW\Twig\TwigExtension(),
    new Twig_Extension_Debug(),
    $catFoodTwigExtension,
    new \PetFoodDB\Twig\NumberFunctionExtension(),
    new \PetFoodDB\Twig\StatsExtension($app),
    new \PetFoodDB\Twig\TextUtilExtension(),
    new \PetFoodDB\Twig\UrlHelperExtension(),
    new Twig_Extensions_Extension_Text(),
    new \Khill\Lavacharts\Symfony\Bundle\Twig\LavachartsExtension($lava)
);

$app->container->singleton('catfood.url', function() use ($catFoodTwigExtension) {
    return $catFoodTwigExtension;
});

$app->container->singleton('logger', function () use ($config) {
    $log = new \Monolog\Logger('main');
    $log->pushHandler(new \Monolog\Handler\StreamHandler($config['php.error_log'], \Monolog\Logger::DEBUG));
    return $log;
});


$app->container->singleton('db', function () use ($config) {
    switch (substr($config['db.dsn'], 0, 5)) {
        // MySQL database
        case 'mysql':
            $db = new \PDO(
                $config['db.dsn'],
                $config['db.username'],
                $config['db.password'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4'
                )
            );
            break;

        // SQLite database
        case 'sqlit':
            $db = new PDO($config['db.dsn']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            break;

        default:
            throw new UnexpectedValueException('Unknown database');
    }

    $structure = new NotORM_Structure_Convention(
        $primary = "id",
        $foreign = "id"
    );


    return new \PetFoodDB\Service\ExposedNotORM($db, $structure);
});

$app->container->singleton('config', function() use ($config) {
    return $config;
});

$app->container->singleton('catfood', function () use ($app) {

    $service = new \PetFoodDB\Service\CatFoodService($app->db);
    $service->setLogger($app->container->get('logger'));

    return $service;
});

$app->container->singleton('shop.service', function () use ($app) {

    $service = new \PetFoodDB\Service\ShopService($app->db);
    $service->setLogger($app->container->get('logger'));

    return $service;
});

$app->container->singleton('catfood.controller', function () use ($app) {
    return new \PetFoodDB\Controller\CatFoodController($app);
});

$app->container->singleton('page.controller', function () use ($app) {
    return new \PetFoodDB\Controller\PageController($app);
});

$app->container->singleton('product.controller', function () use ($app) {
    return new \PetFoodDB\Controller\ProductPageController($app);
});

$app->container->singleton('admin.controller', function () use ($app) {
    return new \PetFoodDB\Controller\Admin\AdminController($app);
});

$app->container->singleton('admin.lists.controller', function () use ($app) {
    return new \PetFoodDB\Controller\Admin\AdminTopListsController($app);
});


$app->container->singleton('admin.charts.controller', function () use ($app) {
    return new \PetFoodDB\Controller\Admin\AdminChartsController($app);
});

$app->container->singleton('blog.controller', function () use ($app) {
    return new \PetFoodDB\Controller\BlogController($app);
});




$app->container->singleton('sitemap.utils', function () use ($app) {
    return new \PetFoodDB\Service\SitemapUtil();
});

$app->container->singleton('manual.data', function () use ($app) {
    $filename = dirname(__FILE__) . '/../resources/manual_entries.yml';
    return new \PetFoodDB\Service\YmlLookup($filename, 'url');
});

$app->container->singleton('api.auth', function () use ($app, $config) {
    $service = new \PetFoodDB\Service\IdService($config['app.auth.enabled']);
    $service->setLogger($app->container->get('logger'));
    return $service;
});

$app->container->singleton('catfood.serializer', function () use ($config) {
    $encoder = new \Symfony\Component\Serializer\Encoder\JsonEncoder();
    $normalizer = new \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer();
    $normalizer->setIgnoredAttributes(['iterator']);
    $serializer = new \Symfony\Component\Serializer\Serializer([$normalizer], [$encoder]);

    return $serializer;
});

$app->container->singleton('amazon.lookup', function () use ($app, $config) {
    $lookup = new \PetFoodDB\Amazon\Lookup(
        $config['amazon.aceess.key'],
        $config['amazon.secret.key'],
        $config['amazon.associate.key']
    );
    $lookup->setLogger($app->container['logger']);

    return $lookup;
});

$app->container->singleton('yaml.dumper', function() use ($app) {
    return new \Symfony\Component\Yaml\Dumper();
});
$app->container->singleton('yaml.parser', function() use ($app) {
    return new \Symfony\Component\Yaml\Parser();
});

$app->container->singleton('stats', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\DBStatsService(
        $app->container->get('catfood'),
        new \Doctrine\Common\Cache\PhpFileCache($config['cache.dir']) 
    );
    $service->setLogger($app->container['logger']);
    return $service;
});

$app->container->singleton('reporting', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\ReportingService($app->container->get('catfood'));
    return $service;
});

$app->container->singleton('ingredient.analysis', function() use ($app, $config) {

    $service = new \PetFoodDB\Service\AnalyzeIngredients();
    $service->setLogger($app->container['logger']);
    return $service;
});




$app->container->singleton('catfood.analysis', function() use ($app, $config) {
    $useNewAlgo = $config['use.new.nutrition.algo'];
    $service = new \PetFoodDB\Service\NewAnalysisService(
        $app->container->get('catfood'),
        $app->container->get('stats'),
        $app->container->get('ingredient.analysis'),
        $useNewAlgo);
    $service->setLogger($app->container['logger']);
    return $service;
});

$app->container->singleton('analysis.access', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\AnalysisWrapper(
        $app->container->get('db'),
        $app->container->get('catfood'),
        $app->container->get('catfood.analysis')
    );

    $service->setLogger($app->container['logger']);
    return $service;
});


$app->container->singleton('brand.info', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\ManufactureInfo(
        $app->container->get('yaml.parser'), $config['db.brands.file']);
    return $service;
});

$app->container->singleton('blog.service', function() use ($app, $config) {
    $parsedown = new Parsedown();
    $parsedown->setMarkupEscaped(false);
    $parsedown->setUrlsLinked(false);
    $markdownParser = new \Mni\FrontYAML\Bridge\Parsedown\ParsedownParser($parsedown);
    $parser = new \Mni\FrontYAML\Parser(null, $markdownParser);

    $service = new \PetFoodDB\Service\Blog($config['path.blog'], $parser, new \Symfony\Component\Filesystem\Filesystem());
    return $service;
});


$app->container->singleton('seo.service', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\SeoService($config['app.base_url'], $app->container->get('catfood.url'));
    return $service;
});

$app->container->singleton('brand.analysis', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\BrandAnalysis(
        $app->container->get('db'),
        $app->container->get('catfood'),
        $app->container->get('catfood.analysis'),
        $app->container->get('analysis.access'));
    return $service;
});

$app->container->singleton('catfood.ranker', function() use ($app, $config) {
    $service = new \PetFoodDB\Service\RankerService(
        $app->container->get('catfood'),
        $app->container->get('catfood.analysis'),
        $app->container->get('analysis.access'),
        $app->container->get('ingredient.analysis'),
        $config['amazon.purchase.url.template']);
    return $service;
});


$app->container->singleton('lava', function() use ($lava) {
    return $lava;
});

$app->container->singleton('charts.service', function() use ($app) {
    return new \PetFoodDB\Service\ChartDataService($app->container->get('lava'));
});

$app->container->singleton('price.lookup', function() use ($app) {
    return new \PetFoodDB\Service\ChewyPriceLookup(new \Goutte\Client(), $app->container->get('shop.service'));
});

$app->container->singleton('price.service', function () use ($app) {

    $service = new \PetFoodDB\Service\PriceService($app->db);
    $service->setLogger($app->container->get('logger'));

    return $service;
});

include("blog.php");


return $app;


