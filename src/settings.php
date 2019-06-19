<?php declare(strict_types=1);

use Monolog\Logger;

$isLive = strpos($_SERVER['HTTP_HOST'], 'localhost') === false;

return [
    'settings' => [
        'displayErrorDetails'    => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Monolog settings
        'logger'                 => [
            'name'  => 'rhelper4',
            'path'  => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => Logger::DEBUG,
        ],
        'database'               => [
            'host'     => $_ENV[$isLive ? 'DB_HOST_LIVE' : 'DB_HOST_DEV'],
            'name'     => $_ENV[$isLive ? 'DB_NAME_LIVE' : 'DB_NAME_DEV'],
            'user'     => $_ENV[$isLive ? 'DB_USER_LIVE' : 'DB_USER_DEV'],
            'password' => $_ENV[$isLive ? 'DB_PW_LIVE' : 'DB_PW_DEV'],
        ],
    ],
];
