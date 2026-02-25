<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates string length with min/max/exact constraints.
 * More specific than Range validator with better error messages for strings.
 */
final class Length implements ValueValidatorInterface
{
    /**
     * Validates string length between min and max.
     *
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return array<string, mixed>
     */
    public static function between(int $min, int $max): array
    {
        return ['min' => $min, 'max' => $max];
    }

    /**
     * Validates minimum string length.
     *
     * @param int $min Minimum length
     * @return array<string, mixed>
     */
    public static function min(int $min): array
    {
        return ['min' => $min];
    }

    /**
     * Validates maximum string length.
     *
     * @param int $max Maximum length
     * @return array<string, mixed>
     */
    public static function max(int $max): array
    {
        return ['max' => $max];
    }

    /**
     * Validates exact string length.
     *
     * @param int $length Exact length required
     * @return array<string, mixed>
     */
    public static function exact(int $length): array
    {
        return ['exact' => $length];
    }

    /**
     * Validates ZIP code length (5 characters).
     *
     * @return array<string, mixed>
     */
    public static function zipCode(): array
    {
        return ['exact' => 5];
    }

    /**
     * Validates phone number length (10-15 characters).
     *
     * @return array<string, mixed>
     */
    public static function phoneNumber(): array
    {
        return ['min' => 10, 'max' => 15];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string';
        }

        $countBytes = $options['countBytes'] ?? false;
        $length = $countBytes ? strlen($value) : mb_strlen($value);
        $unit = $countBytes ? 'bytes' : 'characters';

        // Check exact length
        if (isset($options['exact'])) {
            $exact = $options['exact'];
            if ($length !== $exact) {
                return sprintf('String must be exactly %d %s, got %d', $exact, $unit, $length);
            }
            return null;
        }

        // Check minimum length
        if (isset($options['min']) && $length < $options['min']) {
            $min = $options['min'];
            return sprintf('String too short: minimum %d %s required, got %d', $min, $unit, $length);
        }

        // Check maximum length
        if (isset($options['max']) && $length > $options['max']) {
            $max = $options['max'];
            return sprintf('String too long: maximum %d %s allowed, got %d', $max, $unit, $length);
        }

        return null;
    }
}
