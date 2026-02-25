<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is within a specified range.
 * Supports both numeric values and string length validation.
 */
final class Range implements ValueValidatorInterface
{
    /**
     * Validates a value between min and max.
     *
     * @param int|float $min Minimum value
     * @param int|float $max Maximum value
     * @return array<string, mixed>
     */
    public static function between(int|float $min, int|float $max): array
    {
        return ['min' => $min, 'max' => $max];
    }

    /**
     * Validates a value has a minimum.
     *
     * @param int|float $min Minimum value
     * @return array<string, mixed>
     */
    public static function min(int|float $min): array
    {
        return ['min' => $min];
    }

    /**
     * Validates a value has a maximum.
     *
     * @param int|float $max Maximum value
     * @return array<string, mixed>
     */
    public static function max(int|float $max): array
    {
        return ['max' => $max];
    }

    /**
     * Validates a value is positive (>= 0).
     *
     * @return array<string, mixed>
     */
    public static function positive(): array
    {
        return ['min' => 0];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        $min = $options['min'] ?? PHP_INT_MIN;
        $max = $options['max'] ?? PHP_INT_MAX;

        return match (gettype($value)) {
            'double', 'integer' => $this->validateNumeric($value, $min, $max),
            'string' => $this->validateString($value, $min, $max),
            default => null,
        };
    }

    private function validateNumeric(int|float $value, int|float $min, int|float $max): ?string
    {
        if ($value < $min) {
            return sprintf('Number [%s] too small. Min value is: [%s].', $value, $min);
        }

        if ($value > $max) {
            return sprintf('Number [%s] too big. Max value is: [%s].', $value, $max);
        }

        return null;
    }

    private function validateString(string $value, int $min, int $max): ?string
    {
        $length = strlen($value);

        if ($length < $min) {
            return sprintf('String too short. Min length is: [%d], got: [%d].', $min, $length);
        }

        if ($length > $max) {
            return sprintf('String too long. Max length is: [%d], got: [%d].', $max, $length);
        }

        return null;
    }
}
