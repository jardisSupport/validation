<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisSupport\Contract\Validation\ValueValidatorInterface;

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

        $hasCustomMessage = array_key_exists('message', $options);
        $message = $options['message'] ?? 'Invalid email address';
        $checkDns = $options['checkDns'] ?? false;
        $strict = $options['strict'] ?? false;

        // Strict validation: check before FILTER_VALIDATE_EMAIL since filter rejects
        // some of these patterns with a generic message
        if ($strict) {
            // Reject quoted local parts (e.g. "user name"@example.com)
            if (str_contains($value, '"')) {
                return $hasCustomMessage
                    ? $message
                    : 'Email contains quoted strings (not allowed in strict mode)';
            }

            $atPos = strrpos($value, '@');
            if ($atPos !== false) {
                $domain = substr($value, $atPos + 1);

                // Reject IP address literals (e.g. user@[192.168.1.1])
                if (str_starts_with($domain, '[')) {
                    return $hasCustomMessage
                        ? $message
                        : 'Email contains IP literal domain (not allowed in strict mode)';
                }

                // Reject domains without a dot (e.g. user@localhost)
                if (!str_contains($domain, '.')) {
                    return $hasCustomMessage
                        ? $message
                        : 'Email domain must contain a dot in strict mode';
                }
            }
        }

        // Basic email validation
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $message;
        }

        // Optional DNS check
        if ($checkDns) {
            $atPos = strrpos($value, '@');
            if ($atPos === false) {
                return $hasCustomMessage ? $message : 'Email domain does not exist';
            }
            $domain = substr($value, $atPos + 1);
            if ($domain === '' || !checkdnsrr($domain, 'MX')) {
                return $hasCustomMessage ? $message : 'Email domain does not exist';
            }
        }

        return null;
    }
}
