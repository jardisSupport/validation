<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value equals a specific expected value.
 * Useful for password confirmation, terms acceptance, etc.
 */
final class Equals implements ValueValidatorInterface
{
    /**
     * Static helper: Create options for specific value.
     *
     * @return array<string, mixed>
     */
    public static function value(mixed $expectedValue): array
    {
        return ['expectedValue' => $expectedValue];
    }

    /**
     * Static helper: Use strict comparison.
     *
     * @return array<string, mixed>
     */
    public static function strict(mixed $expectedValue): array
    {
        return ['expectedValue' => $expectedValue, 'strict' => true];
    }

    /**
     * Static helper: Use loose comparison.
     *
     * @return array<string, mixed>
     */
    public static function loose(mixed $expectedValue): array
    {
        return ['expectedValue' => $expectedValue, 'strict' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        $expectedValue = $options['expectedValue'] ?? null;

        // If no expected value is set, allow any value
        if ($expectedValue === null) {
            return null;
        }

        $message = $options['message'] ?? 'Value does not match expected value';
        $strict = $options['strict'] ?? true;

        $isEqual = $strict
            ? $value === $expectedValue
            : $value == $expectedValue;

        if (!$isEqual) {
            return $message;
        }

        return null;
    }
}
