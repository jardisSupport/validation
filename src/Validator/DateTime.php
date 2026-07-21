<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use DateTimeImmutable;
use JardisSupport\Contract\Validation\ValueValidatorInterface;

/**
 * Validates date/time values with format, range, and timezone support.
 */
final class DateTime implements ValueValidatorInterface
{
    /**
     * Static helper: ISO 8601 format validation.
     *
     * @return array<string, mixed>
     */
    public static function iso8601(): array
    {
        return ['format' => 'Y-m-d\TH:i:sP'];
    }

    /**
     * Static helper: Date range validation.
     *
     * @return array<string, mixed>
     */
    public static function between(string $min, string $max, string $format = 'Y-m-d\TH:i:sP'): array
    {
        return ['format' => $format, 'min' => $min, 'max' => $max];
    }

    /**
     * Static helper: Simple date format (YYYY-MM-DD).
     *
     * @return array<string, mixed>
     */
    public static function dateOnly(): array
    {
        return ['format' => 'Y-m-d'];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        $hasCustomMessage = array_key_exists('message', $options);
        $format = $options['format'] ?? 'Y-m-d\TH:i:sP';
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;

        // Native DateTime objects: skip format validation, apply range checks
        if ($value instanceof \DateTimeInterface) {
            return $this->validateRange($value, $format, $min, $max, $hasCustomMessage, $options['message'] ?? null);
        }

        if (!is_string($value)) {
            return 'Date/time must be a string or DateTimeInterface';
        }

        $message = $options['message'] ?? 'Invalid date/time format';

        // Validate format. Use '|' to reset unfilled time parts to 00:00:00
        // for deterministic behavior (without '|', current time is used).
        $dateTime = DateTimeImmutable::createFromFormat($format . '|', $value);
        if ($dateTime === false || $dateTime->format($format) !== $value) {
            return $message;
        }

        return $this->validateRange($dateTime, $format, $min, $max, $hasCustomMessage, $message);
    }

    /**
     * Validates min/max range for a DateTimeInterface value.
     *
     * @param \DateTimeInterface $dateTime
     * @param string $format Format for parsing min/max boundary strings
     * @param string|null $min Minimum date string
     * @param string|null $max Maximum date string
     * @param bool $hasCustomMessage Whether a custom message was provided
     * @param string|null $customMessage The custom message if provided
     * @return string|null Error message or null
     */
    private function validateRange(
        \DateTimeInterface $dateTime,
        string $format,
        ?string $min,
        ?string $max,
        bool $hasCustomMessage = false,
        ?string $customMessage = null
    ): ?string {
        // Use '|' suffix to reset unfilled time parts to 00:00:00.
        // Without '|', createFromFormat uses the current time for missing parts,
        // causing incorrect comparisons with DateTime objects at midnight.
        $normalizedFormat = $format . '|';

        if ($min !== null) {
            $minDate = DateTimeImmutable::createFromFormat($normalizedFormat, $min);
            if ($minDate !== false && $dateTime < $minDate) {
                return $hasCustomMessage ? $customMessage : sprintf('Date/time must be after %s', $min);
            }
        }

        if ($max !== null) {
            $maxDate = DateTimeImmutable::createFromFormat($normalizedFormat, $max);
            if ($maxDate !== false && $dateTime > $maxDate) {
                return $hasCustomMessage ? $customMessage : sprintf('Date/time must be before %s', $max);
            }
        }

        return null;
    }
}
