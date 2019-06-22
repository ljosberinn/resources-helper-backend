<?php declare(strict_types=1);

namespace ResourcesHelper;

use Envms\FluentPDO\{Exception, Query};

class User {

    /** @var Query */
    private $fluent;

    /** @var array */
    private $currentUser;

    public function __construct(Query $pdo) {
        $this->fluent = $pdo;

        if($_SERVER['HTTP_X_FORWARDED_FOR'] === '127.0.0.1') {
            $this->createTable();
        }
    }

    private function createTable(): void {
        $stmt = 'CREATE TABLE IF NOT EXISTS 
        `rhelper`.`user` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `mail` VARCHAR(255) NOT NULL,
            `password` CHAR(100) NOT NULL,
            `registeredAt` INT(10) NOT NULL,
            `lastLogin` INT(10) DEFAULT NULL,
            `lastAction` INT(10) DEFAULT NULL,
            `apiKey` VARCHAR(45) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        )';
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return bool
     * @throws Exception
     */
    public function isUnique(string $column, string $value): bool {
        $query = $this->fluent->from('user')
                              ->select(['id', 'password'])
                              ->where($column, $value)
                              ->fetch();

        if(!empty($query)) {
            $this->currentUser = $query;
            return true;
        }

        return false;
    }

    /**
     * @param array $userData
     *
     * @return int
     * @throws Exception
     */
    public function register(array $userData): int {
        $now = time();

        $values = [
            'mail'         => $userData['mail'],
            'password'     => password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'registeredAt' => $now,
            'lastLogin'    => $now,
            'lastAction'   => $now,
        ];

        $id = (int) $this->fluent->insertInto('user', $values)
                                 ->execute();

        Settings::createDefaultEntry($this->fluent, $id);
        APIQueryHistory::createDefaultEntry($this->fluent, $id);

        return $id;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function login(): int {
        $now = time();

        $set = [
            'lastLogin'  => $now,
            'lastAction' => $now,
        ];

        $this->fluent->update('user', $set, (int) $this->currentUser['id'])
                     ->execute();

        return (int) $this->currentUser['id'];
    }

    public function isCorrectPassword(string $password): bool {
        return password_verify($password, $this->currentUser['password']);
    }

    /**
     * @param Query $fluent
     * @param int   $id
     *
     * @throws Exception
     */
    public static function updateLastAction(Query $fluent, int $id): void {
        $set = [
            'lastAction' => time(),
        ];

        $fluent->update('user', $set, $id)
               ->execute();
    }

    /**
     * Fetches the users profile.
     *
     * @param int  $id
     * @param bool $withToken [JWT recreation indicator]
     *
     * @return array
     * @throws Exception
     */
    public function getAccountData(int $id, bool $withToken): array {
        $accountData = $this->fluent->from('user', $id)
                                    ->select('apiKey')
                                    ->fetch();

        $response = [
            'apiKey'          => $accountData['apiKey'],
            'apiQueryHistory' => APIQueryHistory::get($this->fluent, $id),
            'settings'        => Settings::get($this->fluent, $id),
        ];

        if($withToken) {
            $response['token'] = Token::create($id);
        }

        return $response;
    }

    /**
     * If public, fetches required data to render someones profile.
     *
     * @param int $id
     *
     * @return array
     */
    public function getProfileData(int $id): array {
        return [];
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws Exception
     */
    public function exists(int $id): bool {
        return count($this->fluent->from('user', $id)
                                  ->fetch()) === 1;
    }
}
