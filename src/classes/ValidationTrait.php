<?php declare(strict_types=1);

namespace ResourcesHelper;

/**
 * Contains shared logic of Login and Registration classes
 *
 * Trait ValidationTrait
 * @package ResourcesHelper
 */
trait ValidationTrait {

    private $PASSWORD_PATTERN = '/^.*(?=.{8,})(?=.*[a-zA-Z])(?=.*\d)(?=.*[!#$%&? "]).*$/';
    private $MAIL_PATTERN = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';

    private $MAIL_EXISTS = 'MAIL_EXISTS';
    private $MAIL_PATTERN_INVALID = 'MAIL_PATTERN_INVALID';
    private $PASSWORD_INSECURE = 'PASSWORD_INSECURE';
    private $MISSING_ARGUMENTS = 'Invalid arguments provided.';
    private $UNKNOWN_ACCOUNT = 'UNKNOWN_ACCOUNT';

    public function isValidMail(string $mail): bool {
        return preg_match($this->MAIL_PATTERN, $mail) === 1;
    }

    public function isValidPassword(string $password): bool {
        return preg_match($this->PASSWORD_PATTERN, $password) === 1;
    }

    public function accountExists(string $mail): bool {
        return $this->user->isUnique('mail', $mail);
    }

    /**
     * Must be called to retrieve the error code if $this->getError returned an error
     *
     * @param string $error
     *
     * @return int
     */
    public function getErrorStatus(string $error): int {
        return $error === $this->MISSING_ARGUMENTS ? Status::FORBIDDEN : Status::UNPROCESSABLE_ENTITY;
    }
}
