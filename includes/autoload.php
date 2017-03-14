<?php

define('PROJECT_ROOT', realpath(__DIR__ . '/..'));

require PROJECT_ROOT . '/vendor/autoload.php';

//setup autoloader for our classes
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array('PetFoodDB' => __DIR__ . '/../src'));
$loader->registerNamespaces(array('Common' => __DIR__ . '/../src'));
$loader->registerNamespaces(array('BlogEngine' => __DIR__ . '/../src'));
$loader->register();
