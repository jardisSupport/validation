<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisSupport\Contract\Validation\ValueValidatorInterface;

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

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        $hasCustomMessage = array_key_exists('message', $options);
        $message = $options['message'] ?? null;
        $min = $options['min'] ?? PHP_INT_MIN;
        $max = $options['max'] ?? PHP_INT_MAX;

        return match (gettype($value)) {
            'double', 'integer' => $this->validateNumeric($value, $min, $max, $hasCustomMessage, $message),
            'string' => $this->validateString($value, $min, $max, $hasCustomMessage, $message),
            default => null,
        };
    }

    private function validateNumeric(
        int|float $value,
        int|float $min,
        int|float $max,
        bool $hasCustomMessage,
        ?string $customMessage
    ): ?string {
        if ($value < $min) {
            return $hasCustomMessage
                ? $customMessage
                : sprintf('Number [%s] too small. Min value is: [%s].', $value, $min);
        }

        if ($value > $max) {
            return $hasCustomMessage
                ? $customMessage
                : sprintf('Number [%s] too big. Max value is: [%s].', $value, $max);
        }

        return null;
    }

    private function validateString(
        string $value,
        int|float $min,
        int|float $max,
        bool $hasCustomMessage,
        ?string $customMessage
    ): ?string {
        $length = mb_strlen($value);

        if ($length < $min) {
            return $hasCustomMessage
                ? $customMessage
                : sprintf('String too short. Min length is: [%d], got: [%d].', $min, $length);
        }

        if ($length > $max) {
            return $hasCustomMessage
                ? $customMessage
                : sprintf('String too long. Max length is: [%d], got: [%d].', $max, $length);
        }

        return null;
    }
}
