<?php declare(strict_types=1);

use Slim\App;
use Slim\Http\Response;

return function(App $app) {
    if($_SERVER['SERVER_NAME'] !== 'localhost') {
        $app->add(new Tuupola\Middleware\JwtAuthentication([
            'secret'  => getenv('JWT_SECRET'),
            'secure'  => true,
            'relaxed' => ['localhost'],
            'error'   => function(Response $response, array $arguments) {
                return $response->withHeader('Content-type', 'application/json')
                                ->getBody()
                                ->write((string) json_encode([
                                    'status'  => 'error',
                                    'message' => $arguments['message'],
                                ], JSON_UNESCAPED_SLASHES));
            },
        ]));
    }
};
