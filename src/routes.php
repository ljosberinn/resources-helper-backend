<?php declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use ResourcesHelper\{Status, Registration, Login, Token, Settings, User};

return static function(App $app) {
    $container = $app->getContainer();

    /**
     * Frontend Route /profile/ with auth
     */
    $app->get('/account', function(Request $request, Response $response) use ($container) {
        $container->get('logger')
                  ->info('/profile - uid ' . $container['token']['uid']);

        // flag to indicate re-creation of token; only when previous token is older than 5 minutes
        $withToken = time() > $container['token']['exp'] - 300;

        $user = new User($container->get('db'));

        $output = $user->getAccountData((int) $container['token']['uid'], $withToken);

        return $response->withStatus(Status::OK)
                        ->withHeader(...JSON())
                        ->write(json_encode($output, JSON_NUMERIC_CHECK));
    });

    /**
     * Frontend Route /profile/ without auth
     */
    $app->get('/profile/[{id}]', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info('/profile/' . $args['id']);

        $pdo = $container->get('db');

        $user = new User($pdo);
        $uid  = (int) $args['id'];

        if(!$user->exists($uid)) {
            return $response->withStatus(Status::BAD_REQUEST)
                            ->withHeader(...JSON())
                            ->write(json_encode(['error' => 'UNKNOWN_USER']));
        }

        $settings = new Settings($pdo);

        if(!$settings->hasPublicProfile($uid)) {
            return $response->withStatus(Status::FORBIDDEN)
                            ->withHeader(...JSON())
                            ->write(json_encode(['error' => 'PROFILE_HIDDEN']));
        }

        return $response->withStatus(Status::OK)
                        ->withHeader(...JSON())
                        ->write(json_encode($user->getProfileData($uid), JSON_NUMERIC_CHECK));
    });

    $app->patch('/account/settings/[{type}]', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info('Settings - ' . $args['type'] . ' - uid ' . $container['token']['uid']);
        $output = [];

        $settings = new Settings($container->get('db'));

        if(!in_array($args['type'], $settings::TYPES, true)) {
            return $response->withStatus(Status::FORBIDDEN)
                            ->withHeader(...JSON())
                            ->write(json_encode(['error' => 'unknown settings type ' . $args['type']]));
        }

        $settings->setType($args['type']);
        $settings->update((int) $container['token']['uid'], $request->getParsedBody() ?: []);

        $output['token'] = Token::create((int) $container['token']['uid']);

        return $response->withStatus(Status::ACCEPTED)
                        ->withHeader(...JSON())
                        ->write(json_encode($output));
    });

    $app->post('/login', function(Request $request, Response $response) use ($container) {
        $container->get('logger')
                  ->info('Login');
        $output = [];

        $login = new Login($container->get('db'));

        if($error = $login->getError($request->getParsedBody() ?: [])) {
            $output['error'] = $error;
            $status          = $login->getErrorStatus($error);
        }

        if(!isset($output['error'])) {
            $output['token'] = Token::create($login->login());
        }

        return $response->withStatus($status ?? Status::OK)
                        ->withHeader(...JSON())
                        ->write(json_encode($output));
    });

    $app->post('/register', function(Request $request, Response $response) use ($container) {
        $container->get('logger')
                  ->info('Registration');
        $output = [];

        $userData = $request->getParsedBody() ?: [];

        $registration = new Registration($container->get('db'));

        if($error = $registration->getError($userData)) {
            $output['error'] = $error;
            $status          = $registration->getErrorStatus($error);
        }

        if(!isset($output['error'])) {
            $output['token'] = Token::create($registration->register($userData));
        }

        return $response->withStatus($status ?? Status::CREATED)
                        ->withHeader(...JSON())
                        ->write(json_encode($output));
    });

    $app->get('/', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info("Slim-Skeleton '/' route");

        return $response->withStatus(Status::FORBIDDEN);
    });
};
