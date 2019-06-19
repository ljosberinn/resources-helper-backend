<?php declare(strict_types=1);

namespace ResourcesHelper;

use PDO;

class Settings {

    /** @var PDO */
    private $pdo;

    /** @var string */
    private $type;

    public const TYPES = [
        'rememberAPIKey',
        'language',
    ];

    private const QUERIES = [
        'rememberAPIKey' => 'UPDATE `user` SET `apiKey` = :apiKey WHERE `uid` = :uid',
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function update(int $uid, array $payload): void {
        User::updateLastAction($this->pdo, $uid);

        switch($this->type) {
            case 'rememberAPIKey':
                $stmt = $this->pdo->prepare(self::QUERIES['rememberAPIKey']);
                $stmt->execute([
                    'uid'    => $uid,
                    'apiKey' => $payload['apiKey'] ?? NULL,
                ]);
                break;
            case 'language':

                break;
        }
    }
}
