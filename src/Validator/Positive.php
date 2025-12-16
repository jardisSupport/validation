<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a numeric value is positive (> 0).
 */
final class Positive implements ValueValidatorInterface
{
    /**
     * Static helper: Allow zero (non-negative).
     *
     * @return array<string, mixed>
     */
    public static function allowZero(): array
    {
        return ['allowZero' => true];
    }

    /**
     * Static helper: Strict positive (no zero).
     *
     * @return array<string, mixed>
     */
    public static function strict(): array
    {
        return ['allowZero' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return 'Value must be numeric';
        }

        $message = $options['message'] ?? 'Value must be positive';
        $allowZero = $options['allowZero'] ?? false;

        $numericValue = is_string($value) ? (float) $value : $value;

        if ($allowZero) {
            return $numericValue >= 0 ? null : $message;
        }

        return $numericValue > 0 ? null : $message;
    }
}
