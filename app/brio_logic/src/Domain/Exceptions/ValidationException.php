<?php
declare(strict_types=1);

namespace Brio\Domain\Exceptions;

/** Field-level validation errors (rendered as HTTP 422). */
final class ValidationException extends \RuntimeException
{
    /** @param array<string, string[]> $errors */
    public function __construct(public readonly array $errors, string $message = 'Please fix the highlighted fields.')
    {
        parent::__construct($message);
    }
}
