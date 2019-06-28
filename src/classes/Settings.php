<?php declare(strict_types=1);

namespace ResourcesHelper;

use Envms\FluentPDO\{Query, Exception, Literal};

class Settings {

    /** @var Query */
    private $fluent;

    /** @var string */
    private $type;

    public const TYPES = [
        'rememberAPIKey',
        'language',
    ];

    private const TABLE_NAME = 'settings';

    public function __construct(Query $fluent) {
        $this->fluent = $fluent;

        if($_SERVER['HTTP_X_FORWARDED_FOR'] === '127.0.0.1') {
            $this->createTable();
        }
    }

    private function createTable(): void {
        $stmt = 'CREATE TABLE IF NOT EXISTS 
        `rhelper`.`settings` (
            `id` INT(10) NULL AUTO_INCREMENT,
            `locale` VARCHAR(5) NOT NULL DEFAULT "de_DE",
            `remembersAPIKey` TINYINT(1) NOT NULL DEFAULT "0",
            `apiKey` VARCHAR(45) NULL DEFAULT NULL,
            `hasPublicProfile` TINYINT(1) NOT NULL DEFAULT "0"
        )';
    }

    /**
     * @param Query $fluent
     * @param int   $id
     *
     * @throws Exception
     */
    public static function createDefaultEntry(Query $fluent, int $id): void {
        $values = [
            'id' => $id,
        ];

        $fluent->insertInto(self::TABLE_NAME, $values)
               ->execute();
    }

    /**
     * @param Query $fluent
     * @param int   $id
     *
     * @return mixed
     * @throws Exception
     */
    public static function get(Query $fluent, int $id) {
        $settings = $fluent->from(self::TABLE_NAME, $id)
                           ->fetch();

        unset($settings['id']);

        return $settings;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    /**
     * @param int   $id
     * @param array $payload
     *
     * @throws Exception
     */
    public function update(int $id, array $payload): void {
        User::updateLastAction($this->fluent, $id);

        switch($this->type) {
            case 'rememberAPIKey':
                $set = [
                    'remembersAPIKey' => $payload['apiKey'] ? 1 : 0,
                    'apiKey'          => $payload['apiKey'] ?? new Literal('NULL'),
                ];

                $this->fluent->update(self::TABLE_NAME, $set, $id)
                             ->execute();
                break;
            case 'language':

                break;
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws Exception
     */
    public function hasPublicProfile(int $id): bool {
        return count($this->fluent->from(self::TABLE_NAME)
                                  ->where('hasPublicProfile', 1)
                                  ->where('id', $id)
                                  ->fetch()) === 1;

    }
}
