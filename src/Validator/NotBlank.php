<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is not null.
 */
final class NotBlank implements ValueValidatorInterface
{
    /**
     * Default validation (not null).
     *
     * @param string $message Custom error message
     * @return array<string, mixed>
     */
    public static function required(string $message = 'Field can not be empty'): array
    {
        return ['message' => $message];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        $message = $options['message'] ?? 'Field can not be empty';
        return $value === null ? $message : null;
    }
}
