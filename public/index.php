<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__.$url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__.'/../vendor/autoload.php';

// Create the session
session_start();

// Apply something to the session to hold it open
$_SESSION['last_act'] = date('m/d/Y h:i:s a', time());

// Instantiate the app
$settings = require __DIR__.'/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
$dependencies = require __DIR__.'/../src/dependencies.php';
$dependencies($app);

// Register routes
$routes = require __DIR__.'/../src/routes.php';
$routes($app);

// Run app
$app->run();
