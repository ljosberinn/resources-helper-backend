<?php

use GuzzleHttp\Client;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function(App $app) {
    $container = $app->getContainer();

    $container['httpClient'] = function() {
        $guzzle = new Client();
        return $guzzle;
    };

    $app->get('/[{name}]', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info("Slim-Skeleton '/' route");
    });
};
