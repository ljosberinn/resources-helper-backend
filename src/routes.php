<?php declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use ResourcesHelper\{APIPersistor, APIProcessor, APIQueryHistory, APIRequest, Status, Registration, Login, Token, Settings, User};

return static function(App $app) {
    $container = $app->getContainer();

    $app->group('/api', function(App $app) {

        /**
         * This route is a workaround to counter the weak server condition this app will run in
         */
        $app->post('/processQuery5/{iteration}/{cycles}', function(Request $request, Response $response, array $args) {
            $this->get('logger')
                 ->info('POST /api/processQuery5 - uid ' . $this['token']['id']);

            $id        = (int) $this['token']['id'];
            $iteration = (int) $args['iteration'];
            $cycles    = (int) $args['cycles'];

            $apiProcessor = new APIProcessor(5, $id);
            $apiPersistor = new APIPersistor($this->get('db'), 5, $id);

            $data = $apiProcessor->postProcess($iteration);
            $apiPersistor->persist($data, $iteration === 1);

            return $response->withStatus($iteration === $cycles ? Status::OK : Status::PARTIAL_CONTENT);
        });

        $app->get('/{apiKey}/{query:[0-9]+}', function(Request $request, Response $response, array $args) {
            $this->get('logger')
                 ->info('GET /api/' . $args['query'] . ' - uid ' . $this['token']['id']);

            $query  = (int) $args['query'];
            $apiKey = $args['apiKey'];
            $id     = (int) $this['token']['id'];

            $apiRequest = new APIRequest();

            if(!$apiRequest->isValidAPIKey($apiKey) || !$apiRequest->isValidQuery($query)) {
                return $response->withStatus(Status::UNPROCESSABLE_ENTITY)
                                ->withHeader(...JSON())
                                ->write(json_encode(['error' => 'QUERY_INVALID']));
            }

            $responseInterface = $apiRequest->fetch($query, $apiKey);

            if($responseInterface->getStatusCode() !== 200) {
                return $response->withStatus(Status::SERVICE_UNAVAILABLE)
                                ->withHeader(...JSON())
                                ->write(json_encode(['error' => 'API_UNRESPONSIVE']));
            }

            $db = $this->get('db');

            $apiProcessor = new APIProcessor($query, $id);
            $apiPersistor = new APIPersistor($db, $query, $id);

            $apiResponse = $apiProcessor->parse($query, $responseInterface);

            $output = [
                'response' => $query === 5 ? $apiResponse : $apiPersistor->persist($apiResponse),
            ];

            (new APIQueryHistory($db))->update($id, $query);
            return $response->withStatus(Status::OK)
                            ->withHeader(...JSON())
                            ->write(json_encode($output));
        });
    });


    $app->group('/account', function(App $app) {
        /**
         * Frontend Route /profile/ with auth
         */
        $app->get('/', function(Request $request, Response $response) {
            $this->get('logger')
                 ->info('GET /profile/' . $this['token']['id']);

            // flag to indicate re-creation of token; only when previous token is older than 5 minutes
            $withToken = time() > $this['token']['exp'] - 300;

            $user   = new User($this->get('db'));
            $output = $user->getAccountData((int) $this['token']['id'], $withToken);

            return $response->withStatus(Status::OK)
                            ->withHeader(...JSON())
                            ->write(json_encode($output, JSON_NUMERIC_CHECK));
        });

        $app->patch('/settings/{type}', function(Request $request, Response $response, array $args) {
            $this->get('logger')
                 ->info('PATCH /settings/' . $args['type'] . '/' . $this['token']['id']);
            $output = [];

            $settings = new Settings($this->get('db'));

            if(!in_array($args['type'], $settings::TYPES, true)) {
                return $response->withStatus(Status::NOT_FOUND)
                                ->withHeader(...JSON())
                                ->write(json_encode(['error' => 'unknown settings type ' . $args['type']]));
            }

            $settings->setType($args['type']);
            $settings->update((int) $this['token']['id'], $request->getParsedBody() ?: []);

            $output['token'] = Token::create((int) $this['token']['id']);

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
