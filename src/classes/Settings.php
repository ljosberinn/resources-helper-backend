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
        'rememberAPIKey'     => 'UPDATE `user` SET `apiKey` = :apiKey WHERE `uid` = :uid',
        'createDefaultEntry' => 'INSERT INTO `settings` (`uid`) VALUES(:uid)',
        'get'                => 'SELECT * FROM `settings` WHERE `uid` = :uid',
        'hasPublicProfile'   => 'SELECT `uid` FROM `settings` WHERE `hasPublicProfile` = 1 AND `uid` = :uid',
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;

        if($_SERVER['HTTP_X_FORWARDED_FOR'] === '127.0.0.1') {
            $this->createTable();
        }
    }

    private function createTable(): void {
        $stmt = 'CREATE TABLE IF NOT EXISTS 
        `rhelper`.`settings` (
            `uid` INT(10) NULL AUTO_INCREMENT,
            `language` VARCHAR(5) NOT NULL DEFAULT "de_DE",
            `hasPublicProfile` TINYINT(1) NOT NULL DEFAULT "0"
        )';

        $this->pdo->exec($stmt);
    }

    public static function createDefaultEntry(PDO $pdo, int $uid): void {
        $stmt = $pdo->prepare(self::QUERIES['createDefaultEntry']);
        $stmt->execute([
            'uid' => $uid,
        ]);
    }

    public static function get(PDO $pdo, int $uid) {
        $stmt = $pdo->prepare(self::QUERIES['get']);
        $stmt->execute([
            'uid' => $uid,
        ]);

        $settings = $stmt->fetch();
        unset($settings['uid']);

        return $settings;
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

    public function hasPublicProfile(int $uid): bool {
        $stmt = $this->pdo->prepare(self::QUERIES['hasPublicProfile']);
        $stmt->execute([
            'uid' => $uid,
        ]);

        return $stmt->rowCount() === 1;
    }
}
