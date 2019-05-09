<?php declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\App;
use Tracy\Debugger;

require __DIR__ . '/../vendor/autoload.php';

session_start();

Debugger::enable(Debugger::DETECT);
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$strictMode  = true;

$dotenv = Dotenv::create(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

spl_autoload_register(function($className) {
    $path = __DIR__ . '/../classes/' . $className . '.php';
    if(file_exists($path)) {
        require_once $path;
    }
});

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
