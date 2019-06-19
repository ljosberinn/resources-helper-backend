<?php declare(strict_types=1);

namespace ResourcesHelper;

use PDO;

class Login {
    use ValidationTrait;

    /** @var User */
    private $user;

    public function __construct(PDO $pdo) {
        $this->user = new User($pdo);
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

        if(!$this->accountExists($userData['mail'])) {
            return $this->UNKNOWN_ACCOUNT;
        }

        // todo: try to combine with previous if
        if(!$this->user->isCorrectPassword($userData['password'])) {
            return $this->UNKNOWN_ACCOUNT;
        }

        return false;
    }

    public function login(): int {
        return $this->user->login();
    }
}
