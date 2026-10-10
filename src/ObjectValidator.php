<?php

declare(strict_types=1);

namespace JardisSupport\Validation;

use JardisSupport\Validation\Internal\ValidationContext;
use JardisSupport\Contract\Validation\ValidationResult;
use ReflectionObject;
use ReflectionProperty;

/**
 * Orchestrates recursive validation of object graphs.
 * Automatically detects and validates nested objects and arrays.
 *
 * With `$indexedLists = true` (opt-in) every object found inside a list is keyed
 * `{shortName}[{index}]` in both the error tree and the kinds tree (nested lists
 * append further `[{index}]` segments). Siblings stay separate instead of being
 * merged. Default (`false`) keeps the historic merged `{shortName}` keys.
 */
final class ObjectValidator
{
    public function __construct(
        private readonly ValidatorRegistry $registry,
        private readonly ?ValidationContext $context = null,
        private readonly bool $indexedLists = false
    ) {
    }

    /**
     * Validates an object and its entire object graph.
     *
     * @param object $object
     * @return ValidationResult
     */
    public function validate(object $object): ValidationResult
    {
        $context = $this->context ?? new ValidationContext();
        [$errors, $kinds] = $this->validateRecursive($object, $context, '');

        return new ValidationResult($this->filterEmptyErrors($errors), $this->filterEmptyErrors($kinds));
    }

    /**
     * Recursively validates an object and its nested objects.
     *
     * @param object $object
     * @param ValidationContext $context
     * @param string $indexSuffix list index path of this object, e.g. "[1]" or "[0][2]" ('' outside lists)
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [errors tree, kinds tree]
     */
    private function validateRecursive(object $object, ValidationContext $context, string $indexSuffix): array
    {
        // Prevent circular references
        if ($context->hasVisited($object)) {
            return [[], []];
        }

        $context->markVisited($object);
        $context->enterLevel();

        try {
            $className = $this->getShortClassName($object) . $indexSuffix;
            $errors = [];
            $kinds = [];

            // Execute registered validator for this object type
            if ($validator = $this->registry->getValidator($object)) {
                $result = $validator->validate($object);
                $errors = $result->getErrors();
                $kinds = $result->getKinds();
            }

            // Traverse nested objects
            [$nestedErrors, $nestedKinds] = $this->traverseProperties($object, $context);
            $errors = $this->mergeErrors($errors, $nestedErrors);
            $kinds = $this->mergeErrors($kinds, $nestedKinds);

            return [[$className => $errors], [$className => $kinds]];
        } finally {
            $context->exitLevel();
        }
    }

    /**
     * Traverses object properties to find nested objects and arrays.
     *
     * @param object $object
     * @param ValidationContext $context
     * @return array{0: array<int|string, mixed>, 1: array<int|string, mixed>} [errors tree, kinds tree]
     */
    private function traverseProperties(object $object, ValidationContext $context): array
    {
        $errors = [];
        $kinds = [];
        $reflection = new ReflectionObject($object);
        $properties = $reflection->getProperties(
            ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC
        );

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);

            if (is_object($value)) {
                [$nestedErrors, $nestedKinds] = $this->validateRecursive($value, $context, '');
                $errors = $this->mergeErrors($errors, $nestedErrors);
                $kinds = $this->mergeErrors($kinds, $nestedKinds);
            } elseif (is_array($value)) {
                [$arrayErrors, $arrayKinds] = $this->validateArray($value, $context, '');
                $errors = $this->mergeErrors($errors, $arrayErrors);
                $kinds = $this->mergeErrors($kinds, $arrayKinds);
            }
        }

        return [$errors, $kinds];
    }

    /**
     * Validates an array of values, recursing into nested objects.
     *
     * @param array<mixed> $values
     * @param ValidationContext $context
     * @param string $indexSuffix index path of the enclosing list(s), e.g. "[1]" ('' for the property's list itself)
     * @return array{0: array<int|string, mixed>, 1: array<int|string, mixed>} [errors tree, kinds tree]
     */
    private function validateArray(array $values, ValidationContext $context, string $indexSuffix): array
    {
        $errors = [];
        $kinds = [];

        foreach ($values as $key => $value) {
            $elementSuffix = $this->indexedLists ? $indexSuffix . '[' . $key . ']' : '';

            if (is_object($value)) {
                [$nestedErrors, $nestedKinds] = $this->validateRecursive($value, $context, $elementSuffix);
                $errors = $this->mergeErrors($errors, $nestedErrors);
                $kinds = $this->mergeErrors($kinds, $nestedKinds);
            } elseif (is_array($value)) {
                [$arrayErrors, $arrayKinds] = $this->validateArray($value, $context, $elementSuffix);
                $errors = $this->mergeErrors($errors, $arrayErrors);
                $kinds = $this->mergeErrors($kinds, $arrayKinds);
            }
        }

        return [$errors, $kinds];
    }

    /**
     * Merges error arrays without losing entries on string key collisions.
     *
     * array_merge() lets the second value win for identical string keys — a
     * field name colliding with the lcfirst short class name of a nested
     * object would silently overwrite the field error. Colliding arrays are
     * merged recursively, colliding scalars are collected side by side.
     *
     * @param array<int|string, mixed> $base
     * @param array<int|string, mixed> $additional
     * @return array<int|string, mixed>
     */
    private function mergeErrors(array $base, array $additional): array
    {
        foreach ($additional as $key => $value) {
            if (is_int($key)) {
                $base[] = $value;
                continue;
            }

            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            $existing = is_array($base[$key]) ? $base[$key] : [$base[$key]];
            $incoming = is_array($value) ? $value : [$value];
            $base[$key] = $this->mergeErrors($existing, $incoming);
        }

        return $base;
    }

    /**
     * Extracts the short class name (without namespace).
     *
     * @param object $object
     * @return string
     */
    private function getShortClassName(object $object): string
    {
        $className = get_class($object);
        $pos = strrpos($className, '\\');
        $shortName = $pos !== false ? substr($className, $pos + 1) : $className;

        return lcfirst($shortName);
    }

    /**
     * Recursively filters out empty error arrays.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function filterEmptyErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $filtered[$key] = $this->filterEmptyErrors($value);
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
