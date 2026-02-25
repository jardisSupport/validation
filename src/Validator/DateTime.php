<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use DateTimeImmutable;
use JardisPort\Validation\ValueValidatorInterface;

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

        if (!is_string($value)) {
            return 'Date/time must be a string';
        }

        $format = $options['format'] ?? 'Y-m-d\TH:i:sP';
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;
        $message = $options['message'] ?? 'Invalid date/time format';

        // Validate format
        $dateTime = DateTimeImmutable::createFromFormat($format, $value);
        if ($dateTime === false || $dateTime->format($format) !== $value) {
            return $message;
        }

        // Validate minimum date
        if ($min !== null) {
            $minDate = DateTimeImmutable::createFromFormat($format, $min);
            if ($minDate !== false && $dateTime < $minDate) {
                return sprintf('Date/time must be after %s', $min);
            }
        }

        // Validate maximum date
        if ($max !== null) {
            $maxDate = DateTimeImmutable::createFromFormat($format, $max);
            if ($maxDate !== false && $dateTime > $maxDate) {
                return sprintf('Date/time must be before %s', $max);
            }
        }

        return null;
    }
}
