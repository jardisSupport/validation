<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Internal;

use JardisPort\Validation\ValueValidatorInterface;
use JardisSupport\Validation\CompositeFieldValidator;

/**
 * Fluent builder for configuring field validators.
 * Allows chaining multiple validators to a single field.
 */
final class FieldBuilder
{
    /**
     * @var array<array{class: class-string<ValueValidatorInterface>, args: array<mixed>}>
     */
    private array $validatorConfigs = [];

    public function __construct(
        private readonly string $fieldName,
        private readonly CompositeFieldValidator $composite
    ) {
    }

    /**
     * Adds a validator to this field.
     *
     * @param class-string<ValueValidatorInterface> $validatorClass
     * @param array<mixed> $args Constructor arguments
     * @return self
     */
    public function validates(string $validatorClass, array $args = []): self
    {
        $this->validatorConfigs[] = [
            'class' => $validatorClass,
            'args' => $args,
        ];

        return $this;
    }

    /**
     * Returns to the composite validator to configure another field.
     *
     * @return FieldBuilder
     */
    public function field(string $fieldName): FieldBuilder
    {
        $this->finalize();
        return $this->composite->field($fieldName);
    }

    /**
     * Adds a break validator to this field.
     * If this validator fails, validation stops immediately.
     *
     * @param class-string<ValueValidatorInterface> $validatorClass
     * @param array<mixed> $options Options for validator
     * @return self
     */
    public function breaksOn(string $validatorClass, array $options = []): self
    {
        $this->composite->registerFieldValidator(
            $this->fieldName,
            $validatorClass,
            $options,
            CompositeFieldValidator::VALIDATE_BREAK
        );

        return $this;
    }

    /**
     * Excludes specific fields from validation (useful for partial updates).
     *
     * @param array<string> $fields
     * @return CompositeFieldValidator
     */
    public function excludeFields(array $fields): CompositeFieldValidator
    {
        $this->finalize();
        return $this->composite->excludeFields($fields);
    }

    /**
     * Finalizes the field configuration and returns to composite.
     * This is called implicitly when switching fields or completing configuration.
     *
     * @return CompositeFieldValidator
     */
    public function end(): CompositeFieldValidator
    {
        $this->finalize();
        return $this->composite;
    }

    /**
     * @internal
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @internal
     * @return array<array{class: class-string<ValueValidatorInterface>, args: array<mixed>}>
     */
    public function getValidatorConfigs(): array
    {
        return $this->validatorConfigs;
    }

    /**
     * Registers all validators with the composite.
     */
    private function finalize(): void
    {
        foreach ($this->validatorConfigs as $config) {
            $this->composite->registerFieldValidator(
                $this->fieldName,
                $config['class'],
                $config['args']
            );
        }

        // Clear configs after registration to prevent double-registration
        $this->validatorConfigs = [];
    }
}
