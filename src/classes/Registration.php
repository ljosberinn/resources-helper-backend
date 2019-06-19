<?php declare(strict_types=1);

namespace ResourcesHelper;

use PDO;

class Registration {
    use ValidationTrait;

    /** @var User */
    private $user;

    public function __construct(PDO $pdo) {
        $this->user = new User($pdo);
    }

    public function register(array $userData): int {
        return $this->user->register($userData);
    }

    /**
     * @param array $userData
     *
     * @return bool|string
     */
    public function getError(array $userData) {
        if(empty($userData) || !isset($userData['mail'], $userData['password'])) {
            return $this->MISSING_ARGUMENTS;
        }

        if(!$this->isValidMail($userData['mail'])) {
            return $this->MAIL_PATTERN_INVALID;
        }

        if(!$this->isValidPassword($userData['password'])) {
            return $this->PASSWORD_INSECURE;
        }

        if($this->accountExists($userData['mail'])) {
            return $this->MAIL_EXISTS;
        }

        return false;
    }
}
