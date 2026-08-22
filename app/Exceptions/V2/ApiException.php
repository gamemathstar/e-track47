<?php

namespace App\Exceptions\V2;

use RuntimeException;
use Throwable;

/**
 * Domain-level exception for the v2 API. Carries an HTTP status, a machine `code`,
 * and optional field errors so the handler can render the §6 error contract.
 *
 * Use the named constructors for the common cases the contract calls out
 * (404 / 409 / 403 etc.).
 */
class ApiException extends RuntimeException
{
    public function __construct(
        protected string $errorCode,
        string $message,
        protected int $status = 400,
        protected ?array $fieldErrors = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getFieldErrors(): ?array
    {
        return $this->fieldErrors;
    }

    public static function notFound(string $message = 'The requested resource was not found.'): self
    {
        return new self('not_found', $message, 404);
    }

    public static function conflict(string $message = 'The request conflicts with the current state.'): self
    {
        return new self('conflict', $message, 409);
    }

    public static function forbidden(string $message = 'This action is unauthorized.'): self
    {
        return new self('forbidden', $message, 403);
    }

    public static function unprocessable(string $message, ?array $fieldErrors = null): self
    {
        return new self('validation_error', $message, 422, $fieldErrors);
    }

    public static function badRequest(string $message): self
    {
        return new self('bad_request', $message, 400);
    }
}
