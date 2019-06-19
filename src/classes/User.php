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
        'register'         => 'INSERT INTO `user` (mail, password) VALUES(:mail, :password)',
        'login'            => 'UPDATE `user` SET `lastLogin` = :lastLogin, `lastAction` = :lastAction WHERE `uid` = :uid',
        'updateLastAction' => 'UPDATE `user` SET `lastAction` = :lastAction WHERE `uid` = :uid',
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
            `lastLogin` INT(10) NULL DEFAULT NULL,
            `lastAction` INT(10) NULL DEFAULT NULL,
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

        try {
            $this->pdo->beginTransaction();
            $stmt->execute([
                'mail'     => $userData['mail'],
                'password' => password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ]);
            $this->pdo->commit();

            return (int) $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            $this->pdo->rollBack();
            throw new PDOException($e->getMessage());
        }
    }

    public function login(): int {
        $stmt = $this->pdo->prepare(self::QUERIES['login']);

        $now = time();
        $stmt->execute([
            'lastLogin'  => $now,
            'lastAction' => $now,
            'uid'        => $this->currentUser['uid'],
        ]);

        return (int) $this->currentUser['uid'];
    }

    public function isCorrectPassword(string $password): bool {
        return password_verify($password, $this->currentUser['password']);
    }

    public static function updateLastAction(PDO $pdo, int $uid): void {
        $stmt = $pdo->prepare(self::QUERIES['updateLastAction']);
        $stmt->execute([
            'lastAction' => time(),
            'uid'        => $uid,
        ]);
    }
}
