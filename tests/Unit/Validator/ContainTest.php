<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Contain;
use PHPUnit\Framework\TestCase;

final class ContainTest extends TestCase
{
    private Contain $validator;

    protected function setUp(): void
    {
        $this->validator = new Contain();
    }

    public function testValueInAllowedList(): void
    {
        $allowedValues = ['apple', 'banana', 'orange'];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue('apple', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('banana', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('orange', $options);
        $this->assertNull($result);
    }

    public function testValueNotInAllowedList(): void
    {
        $allowedValues = ['apple', 'banana', 'orange'];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue('grape', $options);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('pear', $options);
        $this->assertIsString($result);
    }

    public function testStrictComparison(): void
    {
        $allowedValues = [1, 2, 3];
        $options = ['allowedValues' => $allowedValues];

        // Strict comparison: 1 and '1' are different
        $result = $this->validator->validateValue(1, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('1', $options);
        $this->assertIsString($result);
    }

    public function testNumericValues(): void
    {
        $allowedValues = [1, 2, 3, 4, 5];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue(3, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(10, $options);
        $this->assertIsString($result);
    }

    public function testBooleanValues(): void
    {
        $allowedValues = [true, false];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue(true, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(false, $options);
        $this->assertNull($result);

        // Strict comparison: 1 is not true
        $result = $this->validator->validateValue(1, $options);
        $this->assertIsString($result);
    }

    public function testEmptyAllowedList(): void
    {
        $result = $this->validator->validateValue('test', ['allowedValues' => []]);
        $this->assertIsString($result);
    }

    public function testNullValue(): void
    {
        $allowedValues = ['apple', 'banana', 'orange'];
        $options = ['allowedValues' => $allowedValues];

        // null is NOT allowed according to the validator implementation
        $result = $this->validator->validateValue(null, $options);
        $this->assertIsString($result);
    }

    public function testNullInAllowedList(): void
    {
        $allowedValues = [null, 'apple', 'banana'];
        $options = ['allowedValues' => $allowedValues];

        // null in the allowed list
        $result = $this->validator->validateValue(null, $options);
        $this->assertIsString($result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom contain error';
        $result = $this->validator->validateValue('test', ['allowedValues' => ['other'], 'message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue('test', ['allowedValues' => ['other']]);
        $this->assertSame('Value is not in allowed list', $result);
    }

    public function testOneOfHelper(): void
    {
        $allowedValues = ['red', 'green', 'blue'];
        $options = Contain::oneOf($allowedValues);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowedValues', $options);
        $this->assertSame($allowedValues, $options['allowedValues']);
    }

    public function testCaseSensitiveStrings(): void
    {
        $allowedValues = ['Apple', 'Banana', 'Orange'];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue('Apple', $options);
        $this->assertNull($result);

        // Case matters
        $result = $this->validator->validateValue('apple', $options);
        $this->assertIsString($result);
    }

    public function testZeroValue(): void
    {
        $allowedValues = [0, 1, 2];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue(0, $options);
        $this->assertNull($result);

        // Strict comparison: '0' is not 0
        $result = $this->validator->validateValue('0', $options);
        $this->assertIsString($result);
    }

    public function testEmptyStringValue(): void
    {
        $allowedValues = ['', 'test', 'other'];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue('', $options);
        $this->assertNull($result);
    }

    public function testMixedTypes(): void
    {
        $allowedValues = [1, '2', true, null];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue(1, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(true, $options);
        $this->assertNull($result);
    }

    public function testArrayValues(): void
    {
        // Arrays in allowed values
        $allowedValues = [[1, 2], [3, 4]];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue([1, 2], $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 3], $options);
        $this->assertIsString($result);
    }

    public function testStatusCodesUseCase(): void
    {
        // Typical use case: allowed HTTP status codes
        $allowedStatuses = [200, 201, 204, 400, 404, 500];
        $options = ['allowedValues' => $allowedStatuses];

        $result = $this->validator->validateValue(200, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(403, $options);
        $this->assertIsString($result);
    }

    public function testEnumLikeUseCase(): void
    {
        // Typical use case: enum-like values
        $allowedRoles = ['admin', 'editor', 'viewer'];
        $options = ['allowedValues' => $allowedRoles];

        $result = $this->validator->validateValue('admin', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('superadmin', $options);
        $this->assertIsString($result);
    }

    public function testSingleAllowedValue(): void
    {
        $options = ['allowedValues' => ['only-this']];

        $result = $this->validator->validateValue('only-this', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('anything-else', $options);
        $this->assertIsString($result);
    }

    public function testFloatingPointNumbers(): void
    {
        $allowedValues = [1.5, 2.5, 3.5];
        $options = ['allowedValues' => $allowedValues];

        $result = $this->validator->validateValue(2.5, $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(2.0, $options);
        $this->assertIsString($result);
    }
}
