<?php

declare(strict_types=1);

namespace JardisSupport\Validation;

use JardisSupport\Validation\Internal\ValidationContext;
use JardisPort\Validation\ValidationResult;
use ReflectionObject;
use ReflectionProperty;

/**
 * Orchestrates recursive validation of object graphs.
 * Automatically detects and validates nested objects and arrays.
 */
final class ObjectValidator
{
    public function __construct(
        private readonly ValidatorRegistry $registry,
        private readonly ?ValidationContext $context = null
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
        $errors = $this->validateRecursive($object, $context);

        return new ValidationResult($this->filterEmptyErrors($errors));
    }

    /**
     * Recursively validates an object and its nested objects.
     *
     * @param object $object
     * @param ValidationContext $context
     * @return array<string, mixed>
     */
    private function validateRecursive(object $object, ValidationContext $context): array
    {
        // Prevent circular references
        if ($context->hasVisited($object)) {
            return [];
        }

        $context->markVisited($object);
        $context->enterLevel();

        $className = $this->getShortClassName($object);
        $errors = [];

        // Execute registered validator for this object type
        if ($validator = $this->registry->getValidator($object)) {
            $result = $validator->validate($object);
            $errors = $result->getErrors();
        }

        // Traverse nested objects
        $nestedErrors = $this->traverseProperties($object, $context);
        $errors = array_merge($errors, $nestedErrors);

        $context->exitLevel();

        return [$className => $errors];
    }

    /**
     * Traverses object properties to find nested objects and arrays.
     *
     * @param object $object
     * @param ValidationContext $context
     * @return array<string, mixed>
     */
    private function traverseProperties(object $object, ValidationContext $context): array
    {
        $errors = [];
        $reflection = new ReflectionObject($object);
        $properties = $reflection->getProperties(
            ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC
        );

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);

            if (is_object($value)) {
                $nestedErrors = $this->validateRecursive($value, $context);
                $errors = array_merge($errors, $nestedErrors);
            } elseif (is_array($value)) {
                $arrayErrors = $this->validateArray($value, $context);
                $errors = array_merge($errors, $arrayErrors);
            }
        }

        return $errors;
    }

    /**
     * Validates an array of values, recursing into nested objects.
     *
     * @param array<mixed> $values
     * @param ValidationContext $context
     * @return array<string, mixed>
     */
    private function validateArray(array $values, ValidationContext $context): array
    {
        $errors = [];

        foreach ($values as $key => $value) {
            if (is_object($value)) {
                $nestedErrors = $this->validateRecursive($value, $context);
                $errors = array_merge($errors, $nestedErrors);
            } elseif (is_array($value)) {
                $arrayErrors = $this->validateArray($value, $context);
                $errors = array_merge($errors, $arrayErrors);
            }
        }

        return $errors;
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
        $shortName = substr($className, strrpos($className, '\\') + 1);

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
