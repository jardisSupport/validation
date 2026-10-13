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
     * Static helper: RFC 3339 date-time validation (the JSON/JavaScript wire format).
     *
     * Accepts `YYYY-MM-DDTHH:MM:SS` with optional fractional seconds and an offset
     * `Z` or `+HH:MM` / `-HH:MM` (RFC 3339 section 5.6; `T` and `Z` may be lower case).
     * The offset is mandatory. Calendar-invalid values (`2026-02-30T10:00:00Z`) and
     * leap seconds (`:60`) are rejected. `min`/`max` boundaries are any string
     * `DateTimeImmutable` can parse.
     *
     * @return array<string, mixed>
     */
    public static function rfc3339(): array
    {
        return ['rfc3339' => true];
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
        $rfc3339 = ($options['rfc3339'] ?? false) === true;
        $format = $rfc3339 ? null : ($options['format'] ?? 'Y-m-d\TH:i:sP');
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

        if ($rfc3339) {
            $parsed = $this->parseRfc3339($value);
            if ($parsed === null) {
                return $message;
            }

            return $this->validateRange($parsed, null, $min, $max, $hasCustomMessage, $message);
        }

        // Validate format. Use '|' to reset unfilled time parts to 00:00:00
        // for deterministic behavior (without '|', current time is used).
        $dateTime = DateTimeImmutable::createFromFormat($format . '|', $value);
        if ($dateTime === false || $dateTime->format($format) !== $value) {
            return $message;
        }

        return $this->validateRange($dateTime, $format, $min, $max, $hasCustomMessage, $message);
    }

    /**
     * Parses a strict RFC 3339 date-time; null when the shape or the calendar is invalid.
     */
    private function parseRfc3339(string $value): ?DateTimeImmutable
    {
        $pattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})[Tt]([0-9]{2}):([0-9]{2}):([0-9]{2})'
            . '(?:\.[0-9]+)?(?:[Zz]|[+-]([0-9]{2}):([0-9]{2}))$/D';
        if (preg_match($pattern, $value, $m) !== 1) {
            return null;
        }

        if (
            !checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59
            || (isset($m[7]) && ((int) $m[7] > 23 || (int) ($m[8] ?? 0) > 59))
        ) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Validates min/max range for a DateTimeInterface value.
     *
     * @param \DateTimeInterface $dateTime
     * @param string|null $format Format for parsing min/max boundary strings
     * @param string|null $min Minimum date string
     * @param string|null $max Maximum date string
     * @param bool $hasCustomMessage Whether a custom message was provided
     * @param string|null $customMessage The custom message if provided
     * @return string|null Error message or null
     */
    private function validateRange(
        \DateTimeInterface $dateTime,
        ?string $format,
        ?string $min,
        ?string $max,
        bool $hasCustomMessage = false,
        ?string $customMessage = null
    ): ?string {
        // Use '|' suffix to reset unfilled time parts to 00:00:00.
        // Without '|', createFromFormat uses the current time for missing parts,
        // causing incorrect comparisons with DateTime objects at midnight.
        // A null format (RFC 3339 mode) parses the boundaries as free-form date strings.
        $parse = static function (string $boundary) use ($format): DateTimeImmutable|false {
            if ($format !== null) {
                return DateTimeImmutable::createFromFormat($format . '|', $boundary);
            }
            try {
                return new DateTimeImmutable($boundary);
            } catch (\Exception) {
                return false;
            }
        };

        if ($min !== null) {
            $minDate = $parse($min);
            if ($minDate !== false && $dateTime < $minDate) {
                return $hasCustomMessage ? $customMessage : sprintf('Date/time must be after %s', $min);
            }
        }

        if ($max !== null) {
            $maxDate = $parse($max);
            if ($maxDate !== false && $dateTime > $maxDate) {
                return $hasCustomMessage ? $customMessage : sprintf('Date/time must be before %s', $max);
            }
        }

        return null;
    }
}
