<?php declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use ResourcesHelper\{APIQueryHistory, Status, Registration, Login, Token, Settings, User};

return static function(App $app) {
    $container = $app->getContainer();

    $app->get('/api/{id}', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info('GET /api/' . $args['id'] . ' - uid ' . $container['token']['uid']);

        $output = ['id' => $args['id']];

        return $response->withStatus($status ?? Status::OK)
                        ->withHeader(...JSON())
                        ->write(json_encode($output));
    });

    $app->group('/account', function(App $app) {
        /**
         * Frontend Route /profile/ with auth
         */
        $app->get('/', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('GET /profile/' . $this['token']['uid']);

            // flag to indicate re-creation of token; only when previous token is older than 5 minutes
            $withToken = time() > $this['token']['exp'] - 300;

            $user   = new User($this->get('db'));
            $output = $user->getAccountData((int) $this['token']['uid'], $withToken);

            return $response->withStatus(Status::OK)
                            ->withHeader(...JSON())
                            ->write(json_encode($output, JSON_NUMERIC_CHECK));
        });

        $app->post('/apiQueryHistory', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('POST /apiQueryHistory/' . $this['token']['uid']);

            $output = ['token' => Token::create($this['token']['uid'])];

            $body    = $request->getParsedBody() ?: [];
            $history = !empty($body) && isset($body['queries']) ? array_values($body['queries']) : [];

            $apiQueryHistory = new APIQueryHistory($this->get('db'));
            $status          = $apiQueryHistory->update((int) $this['token']['uid'], $history) ? Status::OK : Status::BAD_REQUEST;

            return $response->withStatus($status)
                            ->withHeader(...JSON())
                            ->write(json_encode($output));
        });

        $app->get('/apiQueryHistory', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('GET /apiQueryHistory/ ' . $this['token']['uid']);

            return $response->withStatus(Status::OK)
                            ->withHeader(...JSON())
                            ->write(json_encode(APIQueryHistory::get($this->get('db'), (int) $this['token']['uid'])));
        });

        $app->patch('/settings/{type}', function(Request $request, Response $response, array $args) {
            $this->get('logger')
                 ->info('PATCH /settings/' . $args['type'] . '/' . $this['token']['uid']);
            $output = [];

            $settings = new Settings($this->get('db'));

            if(!in_array($args['type'], $settings::TYPES, true)) {
                return $response->withStatus(Status::NOT_FOUND)
                                ->withHeader(...JSON())
                                ->write(json_encode(['error' => 'unknown settings type ' . $args['type']]));
            }

            $settings->setType($args['type']);
            $settings->update((int) $this['token']['uid'], $request->getParsedBody() ?: []);

            $output['token'] = Token::create((int) $this['token']['uid']);

            return $response->withStatus(Status::ACCEPTED)
                            ->withHeader(...JSON())
                            ->write(json_encode($output));
        });
    });

    /**
     * Frontend Route /profile/ without auth
     */
    $app->get('/profile/{id}', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info('GET /profile/' . $args['id']);

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

    $app->group('/auth', function(App $app) use ($container) {
        $app->post('/login', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('POST /auth/login');
            $output = [];

            $login = new Login($this->get('db'));

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

        $app->post('/register', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('POST /auth/register');
            $output = [];

            $userData = $request->getParsedBody() ?: [];

            $registration = new Registration($this->get('db'));

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
    });

    $app->get('/', function(Request $request, Response $response, array $args) use ($container) {
        $container->get('logger')
                  ->info("Slim-Skeleton '/' route");

        return $response->withStatus(Status::FORBIDDEN);
    });
};
