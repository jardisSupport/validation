<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates phone numbers with optional country code validation.
 * Supports E.164 format and various country-specific formats.
 */
final class PhoneNumber implements ValueValidatorInterface
{
    private const COUNTRY_PATTERNS = [
        'DE' => '/^(\+49|0049|0)[1-9][0-9]{1,14}$/',
        'US' => '/^(\+1|1)?[2-9]\d{2}[2-9]\d{6}$/',
        'GB' => '/^(\+44|0044|0)[1-9]\d{9,10}$/',
        'FR' => '/^(\+33|0033|0)[1-9]\d{8}$/',
        'IT' => '/^(\+39|0039)?[0-9]{6,12}$/',
        'ES' => '/^(\+34|0034)?[6-9]\d{8}$/',
        'AT' => '/^(\+43|0043|0)[1-9]\d{3,12}$/',
        'CH' => '/^(\+41|0041|0)[1-9]\d{8}$/',
        'NL' => '/^(\+31|0031|0)[1-9]\d{8}$/',
        'BE' => '/^(\+32|0032|0)[1-9]\d{7,8}$/',
    ];

    /**
     * Static helper: German phone number format.
     *
     * @return array<string, mixed>
     */
    public static function german(): array
    {
        return ['countryCode' => 'DE'];
    }

    /**
     * Static helper: US phone number format.
     *
     * @return array<string, mixed>
     */
    public static function us(): array
    {
        return ['countryCode' => 'US'];
    }

    /**
     * Static helper: International format (E.164).
     *
     * @return array<string, mixed>
     */
    public static function international(): array
    {
        return ['requireInternational' => true];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Phone number must be a string';
        }

        $message = $options['message'] ?? 'Invalid phone number';
        $countryCode = $options['countryCode'] ?? null;
        $requireInternational = $options['requireInternational'] ?? false;

        // Remove common formatting characters
        $cleaned = str_replace([' ', '-', '(', ')', '.'], '', $value);

        // Check if country-specific pattern exists
        if ($countryCode !== null) {
            $countryCodeUpper = strtoupper($countryCode);
            if (!isset(self::COUNTRY_PATTERNS[$countryCodeUpper])) {
                return sprintf('Unsupported country code: %s', $countryCode);
            }

            if (!preg_match(self::COUNTRY_PATTERNS[$countryCodeUpper], $cleaned)) {
                return sprintf('Invalid phone number for country %s', $countryCodeUpper);
            }

            return null;
        }

        // Generic E.164 format validation (international format)
        if ($requireInternational) {
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $cleaned)) {
                return 'Phone number must be in international format (+XX...)';
            }
            return null;
        }

        // Generic phone number validation (very permissive)
        if (!preg_match('/^(\+)?[0-9]{7,15}$/', $cleaned)) {
            return $message;
        }

        return null;
    }
}
