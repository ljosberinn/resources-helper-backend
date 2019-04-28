<?php declare(strict_types=1);

class Localization {

    /** @var PDO $pdo */
    private $pdo;
    /** @var string $locale */
    private $locale;
    /** @var array $error */
    private $error = ['error' => 'locale does not exist'];

    private const QUERIES = [
        'verifyLocale' => 'SHOW COLUMNS FROM `localization`',
    ];

    public function __construct(string $locale) {
        $db        = DB::getInstance();
        $this->pdo = $db->getConnection();

        $this->locale = $locale;
    }

    private function verifyLocale(): bool {
        $stmt = $this->pdo->query(self::QUERIES['verifyLocale']);

        foreach($stmt->fetchAll() as $columm) {
            if($columm['Field'] === $this->locale) {
                return true;
            }
        }

        return false;
    }

    public function get(): string {
        if(!$this->verifyLocale()) {
            return json_encode($this->error);
        }

        $stmt = $this->pdo->query('SELECT `key`, `' . $this->locale . '` FROM `localization`');

        $localization = [];

        if($stmt->rowCount() > 0) {

            foreach($stmt->fetchAll() as $dataset) {
                $localization[$dataset['key']] = $dataset[$this->locale];
            }
        }

        return json_encode($localization);
    }
}
