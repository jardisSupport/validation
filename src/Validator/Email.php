<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is a valid email address.
 * Uses PHP's FILTER_VALIDATE_EMAIL with optional DNS check.
 */
final class Email implements ValueValidatorInterface
{
    /**
     * Basic email validation.
     *
     * @param string $message Custom error message
     * @return array<string, mixed>
     */
    public static function basic(string $message = 'Invalid email address'): array
    {
        return ['message' => $message, 'checkDns' => false];
    }

    /**
     * Email validation with DNS MX record check.
     *
     * @param string $message Custom error message
     * @return array<string, mixed>
     */
    public static function withDnsCheck(string $message = 'Invalid email address'): array
    {
        return ['message' => $message, 'checkDns' => true];
    }

    /**
     * Strict email validation (DNS check enabled).
     *
     * @return array<string, mixed>
     */
    public static function strict(): array
    {
        return ['strict' => true, 'checkDns' => true];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Email must be a string';
        }

        $message = $options['message'] ?? 'Invalid email address';
        $checkDns = $options['checkDns'] ?? false;

        // Basic email validation
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $message;
        }

        // Optional DNS check
        if ($checkDns) {
            $atPos = strrpos($value, '@');
            if ($atPos === false) {
                return 'Email domain does not exist';
            }
            $domain = substr($value, $atPos + 1);
            if ($domain === '' || !checkdnsrr($domain, 'MX')) {
                return 'Email domain does not exist';
            }
        }

        return null;
    }
}
