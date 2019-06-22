<?php declare(strict_types=1);

namespace ResourcesHelper;

use PDO;

class APIQueryHistory {

    /** @var PDO */
    private $pdo;

    private const QUERIES = [
        'createDefaultEntry' => 'INSERT INTO `apiQueryHistory` (`uid`) VALUES(:uid)',
        'get'                => 'SELECT * FROM `apiQueryHistory` WHERE `uid` = :uid',
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public static function createDefaultEntry(PDO $pdo, int $uid): void {
        $stmt = $pdo->prepare(self::QUERIES['createEntry']);
        $stmt->execute([
            'uid' => $uid,
        ]);
    }

    public static function get(PDO $pdo, int $uid): array {
        $stmt = $pdo->prepare(self::QUERIES['get']);
        $stmt->execute([
            'uid' => $uid,
        ]);

        $response = [];

        $data = $stmt->fetch();
        unset($data['uid']);

        foreach($data as $id => $timestamp) {
            $data[$id] = (int) $timestamp;
        }

        $mostRecentQuery = array_reduce($data, static function(int $carry, int $entry) {
            if($entry > $carry) {
                return $entry;
            }

            return $carry;
        }, 0);

        foreach($data as $id => $timestamp) {
            $response[] = [
                'id'        => $id,
                'lastQuery' => $timestamp * 1000,
                'active'    => $timestamp === $mostRecentQuery,
            ];
        }

        return $response;
    }

    public function update(int $uid, array $currentQueries): bool {
        $sql = 'UPDATE `apiQueryHistory` SET ';

        $now = time();

        foreach($currentQueries as $query) {
            $sql .= '`' . $query . '` = ' . $now . ', ';
        }

        $sql = substr($sql, 0, -2);
        $sql .= ' WHERE `uid` = :uid';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'uid' => $uid,
        ]);
    }
}
