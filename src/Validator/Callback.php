<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;
use Closure;

/**
 * Validates a field using a custom callback function.
 * Provides maximum flexibility for complex validation logic.
 */
final readonly class Callback implements ValueValidatorInterface
{
    /**
     * @param Closure(mixed): ?string $callback Function that receives the value and returns null if valid,
     *                                          or error message if invalid
     */
    public function __construct(
        private Closure $callback
    ) {
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        return ($this->callback)($value);
    }
}
