<?php declare(strict_types=1);

namespace ResourcesHelper;

use Envms\FluentPDO\{Exception, Query};

class APIQueryHistory {

    /** @var Query */
    private $fluent;

    private const QUERY_EXECUTION_TOLERANCE = 30; // seconds

    public function __construct(Query $fluent) {
        $this->fluent = $fluent;
    }

    /**
     * @param Query $fluent
     * @param int   $id
     *
     * @throws Exception
     */
    public static function createDefaultEntry(Query $fluent, int $id): void {
        $fluent->insertInto('apiQueryHistory')
               ->values(['id' => $id])
               ->execute();
    }

    /**
     * @param Query $fluent
     * @param int   $id
     *
     * @return array
     * @throws Exception
     */
    public static function get(Query $fluent, int $id): array {
        $data = $fluent->from('apiQueryHistory', $id)
                       ->fetch();
        unset($data['id']);

        $mostRecentQuery = 0;

        foreach($data as $id => $timestamp) {
            $timestamp = (int) $timestamp;

            $data[str_replace('query_', '', $id)] = $timestamp;
            unset($data[$id]);

            if($timestamp > $mostRecentQuery) {
                $mostRecentQuery = $timestamp;
            }
        }

        $response = [];

        $mostRecentQuery -= self::QUERY_EXECUTION_TOLERANCE;

        foreach($data as $id => $timestamp) {
            $response[] = [
                'id'        => $id,
                'lastQuery' => $timestamp * 1000,
                'active'    => $timestamp > $mostRecentQuery,
            ];
        }

        return $response;
    }

    /**
     * @param int $id
     * @param int $query
     *
     * @return bool
     * @throws Exception
     */
    public function update(int $id, int $query): bool {
        if($query === 0) {
            return true;
        }

        $set = [
            'query_' . $query => time(),
        ];

        return (bool) $this->fluent->update('apiQueryHistory')
                                   ->set($set)
                                   ->where('id', $id)
                                   ->execute();
    }
}
