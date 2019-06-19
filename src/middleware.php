<?php declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return static function(App $app) {
    $container = $app->getContainer();

    $app->add(new Tuupola\Middleware\JwtAuthentication([
        'path'      => '/',
        'ignore'    => ['/login', '/register'],
        'secret'    => $_ENV['jwtSecret'],
        'algorithm' => 'HS256',
        'secure'    => true,
        'relaxed'   => ['localhost',],
        'error'     => function(Response $response, array $arguments) {
            $data['status']  = 'error';
            $data['message'] = $arguments['message'];

            return $response->withHeader(...JSON())
                            ->getBody()
                            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        },
        'before'    => function(Request $request, array $arguments) use ($container) {
            $container['token'] = $arguments['decoded'];
        },
    ]));
};
