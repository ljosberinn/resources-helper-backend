<?php declare(strict_types=1);

namespace ResourcesHelper;

use Envms\FluentPDO\Exception;
use Psr\Http\Message\ResponseInterface;

class APIProcessor {

    /** @var int */
    private $query;

    /** @var int */
    private $id;

    /** @var resource */
    private $handle;

    private const BYTES_PER_ITERATION = 400;
    private const MAX_DATASETS_PER_FILE = 500;

    private const QUERY_5_BLUEPRINT = [
        'resourceID'    => 'resourceID',
        'lat'           => 'lat',
        'lon'           => 'lon',
        'built'         => 'builddate',
        'quality'       => 'quality',
        'techQuality'   => 'qualityInclTU',
        'techRate'      => 'fullrate',
        'rawRate'       => 'rawrate',
        'techFactor'    => 'techfactor',
        //'isInHQ'        => 'HQboost', // done manually, left for clarity
        'def1'          => 'def1',
        'def2'          => 'def2',
        'def3'          => 'def3',
        'lastAttack'    => 'lastenemyaction',
        'attackPenalty' => 'attackpenalty',
        'attacks'       => 'attackcount',
        'attacksLost'   => 'attacklost',
    ];

    public function __construct(int $query, int $id) {
        $this->query = $query;
        $this->id    = $id;
    }

    /**
     * @param int               $query
     * @param ResponseInterface $response
     *
     * @return array|int
     */
    public function parse(int $query, ResponseInterface $response) {
        if($query === 5) {
            return $this->parseAsString($response);
        }

        return $this->parseAsJSON($response);
    }

    /**
     * Special case parsing for query 5
     *
     * @param ResponseInterface $response
     *
     * @return int
     */
    private function parseAsString(ResponseInterface $response): int {
        $response = $response->getBody();
        $size     = $response->getSize();


        if($size === NULL) {
            return 0;
        }

        $bytesRead  = 0;
        $iterations = 1;
        $buffer     = '';
        $data       = [];

        $this->createHandle($this->generateFileName($iterations));

        while($bytesRead < $size) {
            $remainingBytes = $size - $bytesRead;
            $hasReachedEOF  = $remainingBytes < self::BYTES_PER_ITERATION;
            $nextBatch      = $hasReachedEOF ? $remainingBytes : self::BYTES_PER_ITERATION;

            $bytes     = $response->read($nextBatch);
            $bytesRead += $nextBatch;

            // remove [ at the beginning
            if($bytesRead === self::BYTES_PER_ITERATION) {
                $bytes = substr($bytes, 1);
            }

            // remove ] at the end
            if($hasReachedEOF) {
                $bytes = substr($bytes, 0, -1);
            }

            $datasetEndingPosition = strpos($bytes, '}');

            if($datasetEndingPosition === false) {
                $buffer .= $bytes;
                continue;
            }

            $partialDataset = substr($bytes, 0, $datasetEndingPosition + 1);
            $dataset        = $buffer . $partialDataset;

            // prepended commas happen ~ 4 times in 2300 datasets
            $hasPrependedComma = strpos($dataset, ',') === 0;
            if($hasPrependedComma) {
                $dataset = substr($dataset, 1);
            }

            $dataset = json_decode($dataset, true);
            $data[]  = $this->applyDataManipulation($dataset);

            if($hasReachedEOF) {
                fwrite($this->handle, json_encode($data));
                fclose($this->handle);
                return $iterations;
            }

            $hasReachedDatasetCapPerFile = count($data) === self::MAX_DATASETS_PER_FILE;

            // finalize JSON, reset counter
            if($hasReachedDatasetCapPerFile) {
                fwrite($this->handle, json_encode($data));
                $iterations++;
                $this->createHandle($this->generateFileName($iterations));
                $data = [];
            }

            // offset of 2 because jsons end with },
            $buffer = substr($bytes, $datasetEndingPosition + 2);
        }

        // shouldn't ever happen, but ya know...
        return 0;
    }

    private function parseAsJSON(ResponseInterface $response): array {
        $response = (string) $response->getBody();
        $response = json_decode($response, true);

        return $response;
    }

    /**
     * @param array $responseDataset
     *
     * @return array
     */
    private function applyDataManipulation(array $responseDataset): array {
        $dataset = [];

        foreach(self::QUERY_5_BLUEPRINT as $newKey => $oldKey) {
            $dataset[$newKey] = $responseDataset[$oldKey];
        }

        $dataset['isInHQ'] = $responseDataset['HQboost'] > 1 ? 1 : 0;

        return $dataset;
    }

    /**
     * Creates a new file with read-write capabilities
     *
     * @param string $fileName
     */
    private function createHandle(string $fileName): void {
        if($this->handle !== NULL) {
            fclose($this->handle);
        }

        $this->handle = fopen($fileName, 'wb');
    }

    private function generateFileName(int $iterations): string {
        return implode('_', [$this->query, $this->id, $iterations]) . '.json';
    }

    public function postProcess(int $iteration): array {
        $fileName = $this->generateFileName($iteration);

        $content = file_get_contents($fileName);
        unlink($fileName);

        return json_decode($content, true);
    }
}
