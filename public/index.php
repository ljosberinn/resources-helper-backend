<?php

use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(static function(string $className): void {
    $className = str_replace('ResourcesHelper\\', '', $className);

    $file = __DIR__ . '/../src/classes/' . $className . '.php';

    if(file_exists($file)) {
        require_once $file;
    }
});

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../');
$dotenv->load();

session_start();

function JSON(): array {
    return ['Content-type', 'application/json'];
}

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app      = new App($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

// Register middleware
$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// Run app
$app->run();
