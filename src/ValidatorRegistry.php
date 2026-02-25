<?php

declare(strict_types=1);

namespace JardisSupport\Validation;

use JardisPort\Validation\ValidatorInterface;

/**
 * Registry that maps object classes to their validators.
 * Supports exact class matching and parent class/interface matching.
 */
final class ValidatorRegistry
{
    /**
     * @var array<class-string, ValidatorInterface>
     */
    private array $validators = [];

    /**
     * Registers a validator for a specific class.
     *
     * @param class-string $className
     * @param ValidatorInterface $validator
     * @return self
     */
    public function register(string $className, ValidatorInterface $validator): self
    {
        $this->validators[$className] = $validator;

        return $this;
    }

    /**
     * Retrieves a validator for the given object.
     *
     * @param object $object
     * @return ValidatorInterface|null
     */
    public function getValidator(object $object): ?ValidatorInterface
    {
        $className = get_class($object);

        // Exact match
        if (isset($this->validators[$className])) {
            return $this->validators[$className];
        }

        // Parent class/interface match
        foreach ($this->validators as $registeredClass => $validator) {
            if (is_a($object, $registeredClass)) {
                return $validator;
            }
        }

        return null;
    }

    /**
     * Checks if a validator is registered for the given object.
     *
     * @param object $object
     * @return bool
     */
    public function hasValidator(object $object): bool
    {
        return $this->getValidator($object) !== null;
    }
}
