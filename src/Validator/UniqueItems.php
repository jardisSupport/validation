<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates that an array contains only unique values.
 * Useful for tag lists, ID collections, etc.
 */
final class UniqueItems implements ValueValidatorInterface
{
    /**
     * Static helper: Use strict comparison.
     *
     * @return array<string, mixed>
     */
    public static function strict(): array
    {
        return ['strict' => true];
    }

    /**
     * Static helper: Use loose comparison.
     *
     * @return array<string, mixed>
     */
    public static function loose(): array
    {
        return ['strict' => false];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return 'Value must be an array';
        }

        if (count($value) === 0) {
            return null;
        }

        $message = $options['message'] ?? 'Array must contain only unique values';
        $strict = $options['strict'] ?? true;

        // Get only the values (handles associative arrays)
        $values = array_values($value);

        // Check for duplicates
        if ($this->hasDuplicates($values, $strict)) {
            $duplicates = $this->findDuplicates($values, $strict);
            return sprintf(
                '%s (duplicates: %s)',
                $message,
                implode(', ', array_map(fn($v) => var_export($v, true), $duplicates))
            );
        }

        return null;
    }

    /**
     * Check if array has duplicates
     *
     * @param array<mixed> $array
     */
    private function hasDuplicates(array $array, bool $strict): bool
    {
        $count = count($array);

        if ($strict) {
            // For strict comparison, we need to compare each item with every other item
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($array[$i] === $array[$j]) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            // For loose comparison, use array_unique with SORT_REGULAR
            return count(array_unique($array, SORT_REGULAR)) !== $count;
        }
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     */
    private function findDuplicates(array $array, bool $strict): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($array as $item) {
            $found = false;

            if ($strict) {
                // Use strict comparison
                foreach ($seen as $seenItem) {
                    if ($item === $seenItem) {
                        $found = true;
                        break;
                    }
                }
            } else {
                // Use loose comparison
                foreach ($seen as $seenItem) {
                    if ($item == $seenItem) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                // Check if not already in duplicates
                $alreadyDuplicate = false;
                foreach ($duplicates as $dup) {
                    if ($item === $dup) {
                        $alreadyDuplicate = true;
                        break;
                    }
                }
                if (!$alreadyDuplicate) {
                    $duplicates[] = $item;
                }
            } else {
                $seen[] = $item;
            }
        }

        return $duplicates;
    }
}
