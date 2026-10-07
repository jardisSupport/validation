<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisSupport\Contract\Validation\MissingValueValidatorInterface;
use JardisSupport\Contract\Validation\ValueValidatorInterface;
use ReflectionException;
use ReflectionObject;

/**
 * Validates that a required child property is present (not null) on the validated value —
 * a single object, or every element of an array.
 *
 * Use it to require a child on a parent property, independent of the position of the parent
 * in the object tree: the check is attached to the field that holds the parent(s), not to the
 * parent's own validator class. The property is read via Reflection, so private properties work.
 */
final class ChildPresence implements ValueValidatorInterface, MissingValueValidatorInterface
{
    /**
     * @param string $childField Property name required on each value
     * @param string|null $message Custom error message; defaults to naming $childField
     * @return array<string, mixed>
     */
    public static function requiredChild(string $childField, ?string $message = null): array
    {
        return ['childField' => $childField, 'message' => $message ?? sprintf('%s can not be empty', $childField)];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        $childField = $options['childField'] ?? null;
        $message = $options['message'] ?? 'Field can not be empty';

        if ($value === null || $childField === null) {
            return null;
        }

        foreach (is_array($value) ? $value : [$value] as $item) {
            if ($this->isChildMissing($item, $childField)) {
                return $message;
            }
        }

        return null;
    }

    private function isChildMissing(mixed $item, string $childField): bool
    {
        if (!is_object($item)) {
            return true;
        }

        try {
            $reflection = new ReflectionObject($item);
            if (!$reflection->hasProperty($childField)) {
                return true;
            }
            $property = $reflection->getProperty($childField);
            $property->setAccessible(true);
            return $property->getValue($item) === null;
        } catch (ReflectionException) {
            return true;
        }
    }
}
