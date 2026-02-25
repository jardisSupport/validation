<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates International Bank Account Numbers (IBAN).
 * Validates format, length by country code, and checksum.
 */
final class Iban implements ValueValidatorInterface
{
    private const COUNTRY_LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16,
        'BG' => 22, 'BH' => 22, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28,
        'CZ' => 24, 'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24,
        'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18,
        'GR' => 27, 'GT' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IS' => 26,
        'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32, 'LI' => 21,
        'LT' => 20, 'LU' => 20, 'LV' => 21, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19,
        'MR' => 27, 'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15, 'PK' => 24, 'PL' => 28,
        'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'SA' => 24, 'SE' => 24,
        'SI' => 19, 'SK' => 24, 'SM' => 27, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22,
        'VG' => 24, 'XK' => 20,
    ];

    /**
     * Static helper: SEPA (Single Euro Payments Area) countries.
     *
     * @return array<string, mixed>
     */
    public static function sepa(): array
    {
        return ['message' => 'Invalid IBAN for SEPA region'];
    }

    /**
     * Static helper: German IBAN.
     *
     * @return array<string, mixed>
     */
    public static function forCountry(string $country): array
    {
        return ['message' => sprintf('Invalid IBAN for country %s', $country)];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'IBAN must be a string';
        }

        $message = $options['message'] ?? 'Invalid IBAN';

        // Remove spaces and convert to uppercase
        $iban = str_replace(' ', '', strtoupper($value));

        // Check basic format (2 letters, 2 digits, alphanumeric)
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return $message;
        }

        // Check country code and length
        $countryCode = substr($iban, 0, 2);
        if (!isset(self::COUNTRY_LENGTHS[$countryCode])) {
            return 'Unknown IBAN country code';
        }

        if (strlen($iban) !== self::COUNTRY_LENGTHS[$countryCode]) {
            return sprintf('Invalid IBAN length for country %s', $countryCode);
        }

        // Validate checksum using mod-97 algorithm
        if (!$this->validateChecksum($iban)) {
            return 'Invalid IBAN checksum';
        }

        return null;
    }

    private function validateChecksum(string $iban): bool
    {
        // Move first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (string) (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod 97
        $mod = 0;
        for ($i = 0; $i < strlen($numeric); $i++) {
            $mod = ($mod * 10 + (int) $numeric[$i]) % 97;
        }

        return $mod === 1;
    }
}
