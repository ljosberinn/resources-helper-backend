<?php declare(strict_types=1);

namespace ResourcesHelper;

use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Insert;
use Envms\FluentPDO\Query;

class APIPersistor {

    /** @var Query */
    private $fluent;

    /** @var int */
    private $id;

    /** @var int */
    private $query;

    public function __construct(Query $fluent, int $query, int $id) {
        $this->fluent = $fluent;
        $this->query  = $query;
        $this->id     = $id;
    }

    /**
     * @param array $data
     * @param bool  $truncate [indicator whether the table should be flushed before inserting]
     *
     * @return bool
     * @throws Exception
     */
    public function persist(array $data, bool $truncate = false): bool {
        switch($this->query) {
            case 1:
                return true;
                break;
            case 2:
                return true;
            case 3:
                return true;
            case 4:
                return true;
            case 5:
                if($truncate) {
                    $this->fluent->delete('mineDetails')
                                 ->where(['userId' => $this->id])
                                 ->execute();
                }

                foreach($data as $dataset) {
                    $dataset['userId'] = $this->id;
                    $this->fluent->insertInto('mineDetails', $dataset)
                                 ->execute();
                }

                return true;
            case 51:
                return true;
            case 6:
                return true;
            case 7:
                return true;
            case 8:
                return true;
            case 9:
                return true;
            case 10:
                return true;
            default:
                return false;
        }
    }
}
