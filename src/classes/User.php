<?php declare(strict_types=1);

namespace ResourcesHelper;

use PDO;
use PDOException;
use RuntimeException;

class User {

    /** @var PDO */
    private $pdo;

    /** @var array */
    private $currentUser;

    private const QUERIES = [
        'uniqueness'       => [
            'mail' => 'SELECT `uid`, `password` FROM `user` WHERE `mail` = :value',
        ],
        'register'         => 'INSERT INTO `user` (`mail`, `password`, `registeredAt`, `lastLogin`, `lastAction`) VALUES(:mail, :password, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP())',
        'login'            => 'UPDATE `user` SET `lastLogin` = UNIX_TIMESTAMP(), `lastAction` = UNIX_TIMESTAMP() WHERE `uid` = :uid',
        'updateLastAction' => 'UPDATE `user` SET `lastAction` = UNIX_TIMESTAMP() WHERE `uid` = :uid',
        'getAccountData'   => 'SELECT `apiKey` FROM `user` WHERE `uid` = :uid',
        'exists'           => 'SELECT `uid` FROM `user` WHERE `uid` = :uid',
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;

        if($_SERVER['HTTP_X_FORWARDED_FOR'] === '127.0.0.1') {
            $this->createTable();
        }
    }

    private function createTable(): void {
        $stmt = 'CREATE TABLE IF NOT EXISTS 
        `rhelper`.`user` (
            `uid` INT(11) NOT NULL AUTO_INCREMENT,
            `mail` VARCHAR(255) NOT NULL,
            `password` CHAR(100) NOT NULL,
            `registeredAt` INT(10) NOT NULL,
            `lastLogin` INT(10) DEFAULT NULL,
            `lastAction` INT(10) DEFAULT NULL,
            `apiKey` VARCHAR(45) NULL DEFAULT NULL,
            PRIMARY KEY (`uid`)
        )';

        $this->pdo->exec($stmt);
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return bool
     * @throws RuntimeException
     */
    public function isUnique(string $column, string $value): bool {
        if(!isset(self::QUERIES['uniqueness'][$column])) {
            throw new RuntimeException(sprintf('Unknown column %s used', $column));
        }

        $sql  = self::QUERIES['uniqueness'][$column];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'value' => $value,
        ]);

        if($stmt->rowCount() === 1) {
            $this->currentUser = $stmt->fetch();
            return true;
        }

        return false;
    }

    public function register(array $userData): int {
        $stmt = $this->pdo->prepare(self::QUERIES['register']);

        $stmt->execute([
            'mail'     => $userData['mail'],
            'password' => password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $uid = (int) $this->pdo->lastInsertId();

        Settings::createDefaultEntry($this->pdo, $uid);
        return $uid;
    }

    public function login(): int {
        $stmt = $this->pdo->prepare(self::QUERIES['login']);
        $stmt->execute([
            'uid' => $this->currentUser['uid'],
        ]);

        return (int) $this->currentUser['uid'];
    }

    public function isCorrectPassword(string $password): bool {
        return password_verify($password, $this->currentUser['password']);
    }

    public static function updateLastAction(PDO $pdo, int $uid): void {
        $stmt = $pdo->prepare(self::QUERIES['updateLastAction']);
        $stmt->execute([
            'uid' => $uid,
        ]);
    }

    /**
     * Fetches the users profile.
     *
     * @param int  $uid
     * @param bool $withToken [JWT recreation indicator]
     *
     * @return array
     */
    public function getAccountData(int $uid, bool $withToken): array {
        $stmt = $this->pdo->prepare(self::QUERIES['getAccountData']);
        $stmt->execute([
            'uid' => $uid,
        ]);

        $accountData = $stmt->fetch();

        $response = [
            'apiKey'   => $accountData['apiKey'],
            'settings' => Settings::get($this->pdo, $uid),
        ];

        if($withToken) {
            $response['token'] = Token::create($uid);
        }

        return $response;
    }

    /**
     * If public, fetches required data to render someones profile.
     *
     * @param int $uid
     *
     * @return array
     */
    public function getProfileData(int $uid): array {
        return [];
    }

    public function exists(int $uid): bool {
        $stmt = $this->pdo->prepare(self::QUERIES['exists']);
        $stmt->execute([
            'uid' => $uid,
        ]);

        return $stmt->rowCount() === 1;
    }
}
