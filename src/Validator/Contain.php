<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that a value is contained in a predefined collection.
 */
final class Contain implements ValueValidatorInterface
{
    /**
     * Static helper: Create options for allowed values.
     *
     * @param array<mixed> $allowedValues
     * @return array<string, mixed>
     */
    public static function oneOf(array $allowedValues): array
    {
        return ['allowedValues' => $allowedValues];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        $allowedValues = $options['allowedValues'] ?? [];
        $message = $options['message'] ?? 'Value is not in allowed list';

        if ($value === null) {
            return $message;
        }

        return in_array($value, $allowedValues, strict: true) ? null : $message;
    }
}
