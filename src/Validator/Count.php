<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;
use Countable;

/**
 * Validates the number of elements in an array or Countable object.
 */
final class Count implements ValueValidatorInterface
{
    /**
     * Static helper: Minimum count.
     *
     * @return array<string, mixed>
     */
    public static function min(int $min): array
    {
        return ['min' => $min];
    }

    /**
     * Static helper: Maximum count.
     *
     * @return array<string, mixed>
     */
    public static function max(int $max): array
    {
        return ['max' => $max];
    }

    /**
     * Static helper: Exact count.
     *
     * @return array<string, mixed>
     */
    public static function exact(int $exact): array
    {
        return ['exact' => $exact];
    }

    /**
     * Static helper: Between min and max count.
     *
     * @return array<string, mixed>
     */
    public static function between(int $min, int $max): array
    {
        return ['min' => $min, 'max' => $max];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value) && !$value instanceof Countable) {
            return 'Value must be an array or Countable';
        }

        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;
        $exact = $options['exact'] ?? null;

        if ($min === null && $max === null && $exact === null) {
            throw new \InvalidArgumentException('At least one of min, max, or exact must be specified');
        }

        if ($exact !== null && ($min !== null || $max !== null)) {
            throw new \InvalidArgumentException('Cannot specify exact with min or max');
        }

        $count = count($value);

        // Exact count validation
        if ($exact !== null) {
            if ($count !== $exact) {
                return sprintf('Must contain exactly %d element(s), got %d', $exact, $count);
            }
            return null;
        }

        // Min validation
        if ($min !== null && $count < $min) {
            return sprintf('Must contain at least %d element(s), got %d', $min, $count);
        }

        // Max validation
        if ($max !== null && $count > $max) {
            return sprintf('Must contain at most %d element(s), got %d', $max, $count);
        }

        return null;
    }
}
