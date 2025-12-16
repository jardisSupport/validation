<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates IP addresses (IPv4 and IPv6).
 * Optionally validates against private ranges and specific versions.
 */
final class Ip implements ValueValidatorInterface
{
    /**
     * Static helper: IPv4 validation.
     *
     * @return array<string, mixed>
     */
    public static function v4(): array
    {
        return ['version' => 'v4'];
    }

    /**
     * Static helper: IPv6 validation.
     *
     * @return array<string, mixed>
     */
    public static function v6(): array
    {
        return ['version' => 'v6'];
    }

    /**
     * Static helper: No private IP ranges allowed.
     *
     * @return array<string, mixed>
     */
    public static function noPrivate(): array
    {
        return ['allowPrivate' => false];
    }

    /**
     * Static helper: Public IPv4 addresses only.
     *
     * @return array<string, mixed>
     */
    public static function publicV4(): array
    {
        return ['version' => 'v4', 'allowPrivate' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'IP address must be a string';
        }

        $message = $options['message'] ?? 'Invalid IP address';
        $version = $options['version'] ?? null;
        $allowPrivate = $options['allowPrivate'] ?? true;
        $allowReserved = $options['allowReserved'] ?? true;

        // Determine validation flags
        $flags = 0;

        if (!$allowPrivate) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }

        if (!$allowReserved) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }

        // Validate based on version
        if ($version === 'v4' || $version === '4') {
            if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | $flags)) {
                return 'Invalid IPv4 address';
            }
        } elseif ($version === 'v6' || $version === '6') {
            if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | $flags)) {
                return 'Invalid IPv6 address';
            }
        } else {
            // Accept both IPv4 and IPv6
            if (!filter_var($value, FILTER_VALIDATE_IP, $flags)) {
                return $message;
            }
        }

        // Additional check for loopback addresses when private IPs are not allowed
        // FILTER_FLAG_NO_PRIV_RANGE doesn't catch loopback addresses
        if (!$allowPrivate) {
            if ($value === '127.0.0.1' || $value === '::1' || str_starts_with($value, '127.')) {
                return 'Private IP addresses are not allowed';
            }
        }

        return null;
    }
}
