<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisSupport\Contract\Validation\ValueValidatorInterface;

/**
 * Validates that a string contains valid JSON.
 * Optionally validates JSON structure (object/array).
 */
final class Json implements ValueValidatorInterface
{
    /**
     * Static helper: Validate JSON object.
     *
     * @return array<string, mixed>
     */
    public static function object(): array
    {
        return ['expectedType' => 'object'];
    }

    /**
     * Static helper: Validate JSON array.
     *
     * @return array<string, mixed>
     */
    public static function array(): array
    {
        return ['expectedType' => 'array'];
    }

    /**
     * Static helper: Set maximum depth.
     *
     * @return array<string, mixed>
     */
    public static function maxDepth(int $depth): array
    {
        return ['maxDepth' => $depth];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'JSON must be a string';
        }

        $hasCustomMessage = array_key_exists('message', $options);
        $message = $options['message'] ?? 'Invalid JSON';
        $expectedType = $options['expectedType'] ?? null;
        $maxDepth = $options['maxDepth'] ?? 512;

        if ($value === '') {
            return $message;
        }

        // Try to decode JSON (keep as object to distinguish between object and array)
        $decoded = json_decode($value, false, $maxDepth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $hasCustomMessage
                ? $message
                : sprintf('Invalid JSON: %s', json_last_error_msg());
        }

        // Validate expected type
        if ($expectedType !== null) {
            if (is_array($decoded)) {
                $actualType = 'array';
            } elseif (is_object($decoded)) {
                $actualType = 'object';
            } else {
                $actualType = gettype($decoded);
                return $hasCustomMessage
                    ? $message
                    : sprintf('JSON must be a %s, got primitive (%s)', $expectedType, $actualType);
            }

            if ($expectedType !== $actualType) {
                return $hasCustomMessage
                    ? $message
                    : sprintf('JSON must be a %s, got %s', $expectedType, $actualType);
            }
        }

        return null;
    }
}
