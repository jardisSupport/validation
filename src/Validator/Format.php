<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value matches a regular expression pattern.
 */
final class Format implements ValueValidatorInterface
{
    /**
     * Static helper: Create options for a regex pattern.
     *
     * @return array<string, mixed>
     */
    public static function pattern(string $pattern): array
    {
        return ['pattern' => $pattern];
    }

    /**
     * Static helper: Alphanumeric with dashes pattern.
     *
     * @return array<string, mixed>
     */
    public static function slug(): array
    {
        return ['pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];
    }

    /**
     * Static helper: Hexadecimal color pattern.
     *
     * @return array<string, mixed>
     */
    public static function hexColor(): array
    {
        return ['pattern' => '/^#[0-9A-Fa-f]{6}$/'];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string';
        }

        $pattern = $options['pattern'] ?? null;
        $message = $options['message'] ?? 'Value is not in correct format';

        if ($pattern === null) {
            throw new \InvalidArgumentException('Pattern must be specified');
        }

        $isValid = (bool) preg_match($pattern, $value);

        return $isValid ? null : $message;
    }
}
