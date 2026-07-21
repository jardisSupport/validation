<?php

declare(strict_types=1);

namespace JardisSupport\Validation;

use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Contract\Validation\ValueValidatorInterface;
use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Validation\Internal\FieldBuilder;
use ReflectionObject;
use ReflectionException;

/**
 * Composite validator with fluent API and singleton validator instances.
 * Decouples validators from field names and ensures optimal performance.
 */
final class CompositeFieldValidator implements ValidatorInterface
{
    public const VALIDATE_NORMAL = 'normal';
    public const VALIDATE_BREAK = 'break';

    /**
     * @var array<string, array<string, array<array{
     *     class: class-string<ValueValidatorInterface>,
     *     options: array<mixed>
     * }>>>
     */
    private array $fieldValidators = [
        self::VALIDATE_NORMAL => [],
        self::VALIDATE_BREAK => [],
    ];

    /**
     * @var array<class-string<ValueValidatorInterface>, ValueValidatorInterface>
     */
    private array $validatorInstances = [];

    /**
     * @var array<string>
     */
    private array $excludeFields = [];

    private string $identityField = 'id';

    private ?FieldBuilder $currentBuilder = null;

    /**
     * Starts configuring validators for a field.
     *
     * @param string $fieldName
     * @return FieldBuilder
     */
    public function field(string $fieldName): FieldBuilder
    {
        // Finalize previous builder if exists
        if ($this->currentBuilder !== null) {
            $this->currentBuilder->end();
        }

        $this->currentBuilder = new FieldBuilder($fieldName, $this);
        return $this->currentBuilder;
    }

    /**
     * Internal method called by FieldBuilder to register validators.
     *
     * @internal
     * @param string $fieldName
     * @param class-string<ValueValidatorInterface> $validatorClass
     * @param array<mixed> $options
     * @param string $validateType
     * @return void
     */
    public function registerFieldValidator(
        string $fieldName,
        string $validatorClass,
        array $options = [],
        string $validateType = self::VALIDATE_NORMAL
    ): void {
        if (!in_array($validateType, [self::VALIDATE_NORMAL, self::VALIDATE_BREAK], strict: true)) {
            throw new \InvalidArgumentException("Invalid validation type: {$validateType}");
        }

        $this->fieldValidators[$validateType][$fieldName][] = [
            'class' => $validatorClass,
            'options' => $options,
        ];
    }

    /**
     * Excludes specific fields from validation (useful for partial updates).
     *
     * @param array<string> $fields
     * @return self
     */
    public function excludeFields(array $fields): self
    {
        $this->excludeFields = array_unique([...$this->excludeFields, ...$fields]);
        return $this;
    }

    /**
     * Sets the identity field name used to distinguish create (null) from update (set).
     * Defaults to 'id'.
     *
     * @param string $fieldName
     * @return self
     */
    public function withIdentityField(string $fieldName): self
    {
        $this->identityField = $fieldName;
        return $this;
    }

    /**
     * Validates the given object.
     *
     * @param object $data
     * @return ValidationResult
     */
    public function validate(object $data): ValidationResult
    {
        // Finalize current builder if still open
        if ($this->currentBuilder !== null) {
            $this->currentBuilder->end();
            $this->currentBuilder = null;
        }

        $errors = [];

        // Check break validators first
        if ($this->shouldBreak($data)) {
            return new ValidationResult($errors);
        }

        // Execute normal validators
        foreach ($this->fieldValidators[self::VALIDATE_NORMAL] as $fieldName => $validatorConfigs) {
            // Skip excluded fields
            if ($this->shouldSkipField($data, $fieldName)) {
                continue;
            }

            $value = $this->extractFieldValue($data, $fieldName);

            foreach ($validatorConfigs as $config) {
                $validator = $this->getValidatorInstance($config['class']);
                $error = $validator->validateValue($value, $config['options']);

                if ($error !== null) {
                    if (!isset($errors[$fieldName])) {
                        $errors[$fieldName] = [];
                    }
                    $errors[$fieldName][] = $error;
                }
            }
        }

        return new ValidationResult($errors);
    }

    /**
     * Checks if any break validator fails.
     *
     * @param object $data
     * @return bool
     */
    private function shouldBreak(object $data): bool
    {
        foreach ($this->fieldValidators[self::VALIDATE_BREAK] as $fieldName => $validatorConfigs) {
            if ($this->shouldSkipField($data, $fieldName)) {
                continue;
            }

            $value = $this->extractFieldValue($data, $fieldName);

            foreach ($validatorConfigs as $config) {
                $validator = $this->getValidatorInstance($config['class']);
                if ($validator->validateValue($value, $config['options']) !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines if a field should be skipped during validation.
     *
     * @param object $data
     * @param string $fieldName
     * @return bool
     */
    private function shouldSkipField(object $data, string $fieldName): bool
    {
        if (in_array($fieldName, $this->excludeFields, strict: true)) {
            $identity = $this->extractFieldValue($data, $this->identityField);
            return $identity === null;
        }

        return false;
    }

    /**
     * Extracts a field value from an object using getter methods or public properties.
     *
     * Resolution order:
     *  1. get{Field}() — PSR standard (getEmail, getId)
     *  2. is{Field}()  — boolean getter (isActive, isPublished)
     *  3. has{Field}() — boolean existence getter (hasPermission)
     *  4. {Field}()    — ucfirst convention (Email, Id)
     *  5. Reflection   — direct property access (fallback)
     *
     * @param object $data
     * @param string $fieldName
     * @return mixed
     */
    private function extractFieldValue(object $data, string $fieldName): mixed
    {
        $ucField = ucfirst($fieldName);

        // 1. PSR getter: getEmail(), getId()
        $getter = 'get' . $ucField;
        if (method_exists($data, $getter)) {
            return $data->$getter();
        }

        // 2. Boolean getter: isActive(), isPublished()
        $isGetter = 'is' . $ucField;
        if (method_exists($data, $isGetter)) {
            return $data->$isGetter();
        }

        // 3. Boolean existence getter: hasPermission(), hasRole()
        $hasGetter = 'has' . $ucField;
        if (method_exists($data, $hasGetter)) {
            return $data->$hasGetter();
        }

        // 4. ucfirst convention: Email(), Id()
        if (method_exists($data, $ucField)) {
            return $data->$ucField();
        }

        // 5. Direct property access via reflection
        try {
            $reflection = new ReflectionObject($data);
            if ($reflection->hasProperty($fieldName)) {
                $property = $reflection->getProperty($fieldName);
                $property->setAccessible(true);
                return $property->getValue($data);
            }
        } catch (ReflectionException) {
            // Property not found
        }

        return null;
    }

    /**
     * Gets or creates a singleton instance of a validator.
     *
     * @param class-string<ValueValidatorInterface> $validatorClass
     * @return ValueValidatorInterface
     */
    private function getValidatorInstance(string $validatorClass): ValueValidatorInterface
    {
        if (!isset($this->validatorInstances[$validatorClass])) {
            $this->validatorInstances[$validatorClass] = new $validatorClass();
        }

        return $this->validatorInstances[$validatorClass];
    }
}
