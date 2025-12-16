<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\NotBlank;
use PHPUnit\Framework\TestCase;

final class NotBlankTest extends TestCase
{
    private NotBlank $validator;

    protected function setUp(): void
    {
        $this->validator = new NotBlank();
    }

    public function testNonNullValuesAreValid(): void
    {
        $validValues = [
            '',
            0,
            '0',
            false,
            [],
            'string',
            123,
            0.0,
        ];

        foreach ($validValues as $value) {
            $result = $this->validator->validateValue($value);
            $this->assertNull($result, "Expected value to be valid: " . var_export($value, true));
        }
    }

    public function testNullValueReturnsError(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertIsString($result);
        $this->assertStringContainsString('empty', $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertSame('Field can not be empty', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'This field is required';
        $result = $this->validator->validateValue(null, ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testCustomErrorMessageViaOptions(): void
    {
        $customMessage = 'Please provide a value';
        $result = $this->validator->validateValue(null, ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testEmptyStringIsValid(): void
    {
        // NotBlank only checks for null, not empty strings
        $result = $this->validator->validateValue('');
        $this->assertNull($result);
    }

    public function testZeroIsValid(): void
    {
        $result = $this->validator->validateValue(0);
        $this->assertNull($result);

        $result = $this->validator->validateValue('0');
        $this->assertNull($result);

        $result = $this->validator->validateValue(0.0);
        $this->assertNull($result);
    }

    public function testFalseIsValid(): void
    {
        $result = $this->validator->validateValue(false);
        $this->assertNull($result);
    }

    public function testEmptyArrayIsValid(): void
    {
        $result = $this->validator->validateValue([]);
        $this->assertNull($result);
    }

    public function testWhitespaceStringIsValid(): void
    {
        // NotBlank only checks for null
        $result = $this->validator->validateValue('   ');
        $this->assertNull($result);

        $result = $this->validator->validateValue("\t\n");
        $this->assertNull($result);
    }

    public function testRequiredHelper(): void
    {
        $options = NotBlank::required();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('message', $options);
        $this->assertSame('Field can not be empty', $options['message']);
    }

    public function testRequiredHelperWithCustomMessage(): void
    {
        $customMessage = 'Custom required message';
        $options = NotBlank::required($customMessage);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('message', $options);
        $this->assertSame($customMessage, $options['message']);
    }

    public function testObjectsAreValid(): void
    {
        $result = $this->validator->validateValue(new \stdClass());
        $this->assertNull($result);
    }

    public function testNegativeNumbersAreValid(): void
    {
        $result = $this->validator->validateValue(-1);
        $this->assertNull($result);

        $result = $this->validator->validateValue(-0.5);
        $this->assertNull($result);
    }
}
