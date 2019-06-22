<?php declare(strict_types=1);

namespace ResourcesHelper;

use GuzzleHttp\Client;

class APIRequest {

    private const API_ENDPOINT = 'https://www.resources-game.ch/';
    private const API_ENDPOINT_URI = '/resapi';

    private const OUTPUT_TYPES = [
        'csv'  => 0,
        'json' => 1,
    ];
    private const DEFAULT_OUTPUT_TYPE = 'json';

    private const LANGUAGES = [
        'german'     => 'de',
        'english'    => 'en',
        'russian'    => 'ru',
        'japanese'   => 'ja',
        'indonesian' => 'in',
        'spanish'    => 'es',
        'french'     => 'fr',
    ];
    private const DEFAULT_LANGUAGE = 'english';

    private const DAYS = 30;

    public const VALID_QUERIES = [
        0, // API-Credits
        1, // Factories
        2, // Warehouses
        3, // Special Buildings
        4, // HQ Progress
        5, // Mine Details
        51, // Mine Summary
        6, // Trade Log
        7, // Player Info
        8, // Monetary Items
        9, // Combat Log
        10, // Missions
    ];

    /** @var Client */
    private $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => self::API_ENDPOINT,
            'headers'  => [
                'User-Agent' => 'Resources Helper/4.0-' . strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? 'LIVE' : 'TESTING',
            ],
        ]);
    }

    public function isValidQuery(int $query): bool {
        return in_array($query, self::VALID_QUERIES, true);
    }

    public function isValidAPIKey(string $apiKey): bool {
        return ctype_alnum($apiKey) && strlen($apiKey) === 45;
    }

    public function fetch(int $query, string $apiKey): ?array {
        $response = $this->client->get(self::API_ENDPOINT_URI, [
            'query' => $this->getDefaultParams($query, $apiKey),
        ]);

        if($response->getStatusCode() !== 200) {
            return NULL;
        }

        $body = (string) $response->getBody();

        return json_decode($body, true);
    }

    private function getDefaultParams(int $query, string $apiKey): array {
        return [
            'q' => $query,
            'k' => $apiKey,
            'd' => self::DAYS,
            'l' => self::LANGUAGES[self::DEFAULT_LANGUAGE],
            'f' => self::OUTPUT_TYPES[self::DEFAULT_OUTPUT_TYPE],
        ];
    }
}
