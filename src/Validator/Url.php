<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is a valid URL.
 * Supports protocol restrictions and localhost filtering.
 */
final class Url implements ValueValidatorInterface
{
    /**
     * Static helper: HTTPS only URLs.
     *
     * @return array<string, mixed>
     */
    public static function httpsOnly(): array
    {
        return ['allowedProtocols' => ['https']];
    }

    /**
     * Static helper: No localhost allowed.
     *
     * @return array<string, mixed>
     */
    public static function noLocalhost(): array
    {
        return ['allowLocalhost' => false];
    }

    /**
     * Static helper: HTTPS only and no localhost.
     *
     * @return array<string, mixed>
     */
    public static function secure(): array
    {
        return ['allowedProtocols' => ['https'], 'allowLocalhost' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'URL must be a string';
        }

        $message = $options['message'] ?? 'Invalid URL';
        $allowedProtocols = $options['allowedProtocols'] ?? null;
        $allowLocalhost = $options['allowLocalhost'] ?? true;

        // Parse URL components first
        $parts = parse_url($value);
        if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
            return $message;
        }

        // Basic URL validation
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $message;
        }

        $scheme = strtolower($parts['scheme']);

        // Block dangerous protocols
        $dangerousProtocols = ['javascript', 'data', 'vbscript', 'file'];
        if (in_array($scheme, $dangerousProtocols, strict: true)) {
            return $message;
        }

        // Check protocol restrictions
        if ($allowedProtocols !== null) {
            if (!in_array($scheme, array_map('strtolower', $allowedProtocols), strict: true)) {
                $allowed = implode(', ', $allowedProtocols);
                return "URL protocol must be one of: {$allowed}";
            }
        } else {
            // If no allowed protocols specified, only allow common protocols
            $commonProtocols = ['http', 'https', 'ftp', 'ftps', 'ssh', 'sftp', 'ws', 'wss'];
            if (!in_array($scheme, $commonProtocols, strict: true)) {
                return $message;
            }
        }

        // Check localhost restriction
        if (!$allowLocalhost) {
            $host = strtolower($parts['host']);
            // Remove brackets from IPv6 addresses
            $host = trim($host, '[]');

            if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || str_starts_with($host, '127.')) {
                return 'Localhost URLs are not allowed';
            }
        }

        return null;
    }
}
