<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value contains only alphanumeric characters.
 * Optionally allows additional characters via whitelist.
 */
final class Alphanumeric implements ValueValidatorInterface
{
    /**
     * Static helper: Allow dashes in alphanumeric values.
     *
     * @return array<string, mixed>
     */
    public static function withDashes(): array
    {
        return ['additionalChars' => '-'];
    }

    /**
     * Static helper: Allow spaces in alphanumeric values.
     *
     * @return array<string, mixed>
     */
    public static function withSpaces(): array
    {
        return ['allowSpaces' => true];
    }

    /**
     * Static helper: Allow underscores in alphanumeric values.
     *
     * @return array<string, mixed>
     */
    public static function withUnderscores(): array
    {
        return ['additionalChars' => '_'];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string';
        }

        if ($value === '') {
            return null;
        }

        $message = $options['message'] ?? 'Value must be alphanumeric';
        $additionalChars = $options['additionalChars'] ?? '';
        $allowSpaces = $options['allowSpaces'] ?? false;

        // Build pattern
        $additionalPattern = '';
        if ($additionalChars !== '') {
            $additionalPattern = preg_quote($additionalChars, '/');
        }

        $spacePattern = $allowSpaces ? '\s' : '';
        $pattern = '/^[a-zA-Z0-9' . $additionalPattern . $spacePattern . ']+$/';

        if (!preg_match($pattern, $value)) {
            return $message;
        }

        return null;
    }
}
