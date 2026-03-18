<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is a valid UUID.
 * Supports UUID versions 1, 3, 4, and 5.
 */
final class Uuid implements ValueValidatorInterface
{
    /**
     * Any UUID version.
     *
     * @return array<string, mixed>
     */
    public static function any(): array
    {
        return [];
    }

    /**
     * UUID version 1 (time-based).
     *
     * @return array<string, mixed>
     */
    public static function v1(): array
    {
        return ['version' => 1];
    }

    /**
     * UUID version 3 (MD5 hash).
     *
     * @return array<string, mixed>
     */
    public static function v3(): array
    {
        return ['version' => 3];
    }

    /**
     * UUID version 4 (random).
     *
     * @return array<string, mixed>
     */
    public static function v4(): array
    {
        return ['version' => 4];
    }

    /**
     * UUID version 5 (SHA-1 hash).
     *
     * @return array<string, mixed>
     */
    public static function v5(): array
    {
        return ['version' => 5];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'UUID must be a string';
        }

        $hasCustomMessage = array_key_exists('message', $options);
        $message = $options['message'] ?? 'Invalid UUID format';
        $version = $options['version'] ?? null;

        // Standard UUID format: 8-4-4-4-12 hexadecimal digits
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        if (!preg_match($pattern, $value)) {
            return $message;
        }

        // Validate variant bits (RFC 4122): position 19 must be [89abAB]
        $variantChar = strtolower($value[19]);
        if (!in_array($variantChar, ['8', '9', 'a', 'b'], true)) {
            return $hasCustomMessage ? $message : 'Invalid UUID variant (must be RFC 4122)';
        }

        // Validate version if specified
        if ($version !== null) {
            $versionChar = $value[14];
            if ((int) $versionChar !== $version) {
                return $hasCustomMessage ? $message : sprintf('UUID must be version %d', $version);
            }
        }

        return null;
    }
}
