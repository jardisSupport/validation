<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is not empty.
 * Checks for null, empty strings, empty arrays, and whitespace-only strings.
 * More strict than NotBlank which only checks for null.
 */
final class NotEmpty implements ValueValidatorInterface
{
    /**
     * Static helper: Trim whitespace before checking.
     *
     * @return array<string, mixed>
     */
    public static function trimmed(): array
    {
        return ['trimWhitespace' => true];
    }

    /**
     * Static helper: Strict mode (no trimming).
     *
     * @return array<string, mixed>
     */
    public static function strict(): array
    {
        return ['trimWhitespace' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        $message = $options['message'] ?? 'Field must not be empty';
        $trimWhitespace = $options['trimWhitespace'] ?? true;

        // Null is empty
        if ($value === null) {
            return $message;
        }

        // Empty array is empty
        if (is_array($value) && count($value) === 0) {
            return $message;
        }

        // Check strings
        if (is_string($value)) {
            $checkValue = $trimWhitespace ? trim($value) : $value;
            if ($checkValue === '') {
                return $message;
            }
        }

        // Zero and false are considered non-empty
        return null;
    }
}
