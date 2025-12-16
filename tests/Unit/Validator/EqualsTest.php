<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Equals;
use PHPUnit\Framework\TestCase;

final class EqualsTest extends TestCase
{
    private Equals $validator;

    protected function setUp(): void
    {
        $this->validator = new Equals();
    }

    public function testStrictEquality(): void
    {
        $result = $this->validator->validateValue('test', ['expectedValue' => 'test', 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(123, ['expectedValue' => 123, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(true, ['expectedValue' => true, 'strict' => true]);
        $this->assertNull($result);
    }

    public function testStrictInequalityWithDifferentTypes(): void
    {
        $result = $this->validator->validateValue('123', ['expectedValue' => 123, 'strict' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(1, ['expectedValue' => true, 'strict' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('0', ['expectedValue' => 0, 'strict' => true]);
        $this->assertIsString($result);
    }

    public function testLooseEquality(): void
    {
        $result = $this->validator->validateValue('123', ['expectedValue' => 123, 'strict' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1, ['expectedValue' => true, 'strict' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('0', ['expectedValue' => 0, 'strict' => false]);
        $this->assertNull($result);
    }

    public function testLooseInequality(): void
    {
        $result = $this->validator->validateValue('test', ['expectedValue' => 'other', 'strict' => false]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(123, ['expectedValue' => 456, 'strict' => false]);
        $this->assertIsString($result);
    }

    public function testDefaultIsStrictMode(): void
    {
        // Default should be strict
        $result = $this->validator->validateValue('123', ['expectedValue' => 123]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(123, ['expectedValue' => 123]);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);

        $result = $this->validator->validateValue(null, ['expectedValue' => 'test']);
        $this->assertNull($result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom equals error';
        $result = $this->validator->validateValue('test', ['expectedValue' => 'other', 'message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue('test', ['expectedValue' => 'other']);
        $this->assertSame('Value does not match expected value', $result);
    }

    public function testBooleanValues(): void
    {
        $result = $this->validator->validateValue(true, ['expectedValue' => true, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(false, ['expectedValue' => false, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(true, ['expectedValue' => false, 'strict' => true]);
        $this->assertIsString($result);
    }

    public function testNumericValues(): void
    {
        $result = $this->validator->validateValue(0, ['expectedValue' => 0, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(0.0, ['expectedValue' => 0.0, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(-1, ['expectedValue' => -1, 'strict' => true]);
        $this->assertNull($result);
    }

    public function testArrayValues(): void
    {
        $result = $this->validator->validateValue([1, 2, 3], ['expectedValue' => [1, 2, 3], 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2, 3], ['expectedValue' => [1, 2, 4], 'strict' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(['a' => 1], ['expectedValue' => ['a' => 1], 'strict' => true]);
        $this->assertNull($result);
    }

    public function testEmptyValues(): void
    {
        $result = $this->validator->validateValue('', ['expectedValue' => '', 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([], ['expectedValue' => [], 'strict' => true]);
        $this->assertNull($result);
    }

    public function testValueHelper(): void
    {
        $options = Equals::value('test');
        $this->assertIsArray($options);
        $this->assertArrayHasKey('expectedValue', $options);
        $this->assertSame('test', $options['expectedValue']);
    }

    public function testStrictHelper(): void
    {
        $options = Equals::strict(123);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('expectedValue', $options);
        $this->assertSame(123, $options['expectedValue']);
        $this->assertArrayHasKey('strict', $options);
        $this->assertTrue($options['strict']);
    }

    public function testLooseHelper(): void
    {
        $options = Equals::loose(123);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('expectedValue', $options);
        $this->assertSame(123, $options['expectedValue']);
        $this->assertArrayHasKey('strict', $options);
        $this->assertFalse($options['strict']);
    }

    public function testTypeCoercionInLooseMode(): void
    {
        // String to number
        $result = $this->validator->validateValue('42', ['expectedValue' => 42, 'strict' => false]);
        $this->assertNull($result);

        // Boolean to number
        $result = $this->validator->validateValue(1, ['expectedValue' => true, 'strict' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(0, ['expectedValue' => false, 'strict' => false]);
        $this->assertNull($result);

        // Empty string to false
        $result = $this->validator->validateValue('', ['expectedValue' => false, 'strict' => false]);
        $this->assertNull($result);
    }

    public function testFloatingPointNumbers(): void
    {
        $result = $this->validator->validateValue(1.5, ['expectedValue' => 1.5, 'strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1.5, ['expectedValue' => 1.500, 'strict' => true]);
        $this->assertNull($result);
    }

    public function testCaseSensitiveStrings(): void
    {
        $result = $this->validator->validateValue('Test', ['expectedValue' => 'test', 'strict' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('test', ['expectedValue' => 'test', 'strict' => true]);
        $this->assertNull($result);
    }

    public function testNullExpectedValue(): void
    {
        $result = $this->validator->validateValue('test', ['expectedValue' => null, 'strict' => true]);
        $this->assertNull($result);
    }

    public function testPasswordConfirmationUseCase(): void
    {
        // Typical use case: password confirmation
        $password = 'mySecretPassword123';
        $result = $this->validator->validateValue($password, ['expectedValue' => $password, 'strict' => true]);
        $this->assertNull($result);

        $wrongPassword = 'wrongPassword';
        $result = $this->validator->validateValue($wrongPassword, ['expectedValue' => $password, 'strict' => true]);
        $this->assertIsString($result);
    }
}
