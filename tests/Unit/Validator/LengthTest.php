<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Length;
use PHPUnit\Framework\TestCase;

final class LengthTest extends TestCase
{
    private Length $validator;

    protected function setUp(): void
    {
        $this->validator = new Length();
    }

    public function testValidLengthWithinRange(): void
    {
        $validStrings = [
            'hello',
            'test',
            'world',
            'abc',
        ];

        foreach ($validStrings as $string) {
            $result = $this->validator->validateValue($string, ['min' => 3, 'max' => 10]);
            $this->assertNull($result, "Expected '{$string}' to be valid");
        }
    }

    public function testStringTooShort(): void
    {
        $result = $this->validator->validateValue('ab', ['min' => 3, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('3', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function testStringTooLong(): void
    {
        $result = $this->validator->validateValue('verylongstring', ['min' => 3, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('14', $result);
    }

    public function testMinimumOnly(): void
    {
        $result = $this->validator->validateValue('hello world', ['min' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('hi', ['min' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('5', $result);
    }

    public function testMaximumOnly(): void
    {
        $result = $this->validator->validateValue('hello', ['max' => 10]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('verylongstring', ['max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('10', $result);
    }

    public function testExactLength(): void
    {
        $result = $this->validator->validateValue('12345', ['exact' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('1234', ['exact' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('4', $result);

        $result = $this->validator->validateValue('123456', ['exact' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('6', $result);
    }

    public function testBoundaryValues(): void
    {
        $result = $this->validator->validateValue('abc', ['min' => 3, 'max' => 10]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('abcdefghij', ['min' => 3, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testEmptyString(): void
    {
        $result = $this->validator->validateValue('', ['min' => 1]);
        $this->assertIsString($result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('0', $result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null, ['min' => 5, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(123, ['min' => 3, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array'], ['min' => 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testMultibyteCharacters(): void
    {
        // UTF-8 string with 5 characters (not bytes)
        $result = $this->validator->validateValue('こんにちは', ['exact' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('こんにちは', ['min' => 3, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testByteCountOption(): void
    {
        // UTF-8 string: 5 characters = 15 bytes
        $string = 'こんにちは';

        // Character count (default)
        $result = $this->validator->validateValue($string, ['exact' => 5]);
        $this->assertNull($result);

        // Byte count
        $result = $this->validator->validateValue($string, ['exact' => 15, 'countBytes' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue($string, ['exact' => 5, 'countBytes' => true]);
        $this->assertIsString($result);
        $this->assertStringContainsString('bytes', $result);
    }

    public function testBetweenHelper(): void
    {
        $options = Length::between(5, 10);
        $this->assertIsArray($options);
        $this->assertSame(5, $options['min']);
        $this->assertSame(10, $options['max']);
    }

    public function testMinHelper(): void
    {
        $options = Length::min(3);
        $this->assertIsArray($options);
        $this->assertSame(3, $options['min']);
        $this->assertArrayNotHasKey('max', $options);
    }

    public function testMaxHelper(): void
    {
        $options = Length::max(100);
        $this->assertIsArray($options);
        $this->assertSame(100, $options['max']);
        $this->assertArrayNotHasKey('min', $options);
    }

    public function testExactHelper(): void
    {
        $options = Length::exact(8);
        $this->assertIsArray($options);
        $this->assertSame(8, $options['exact']);
    }

    public function testZipCodeHelper(): void
    {
        $options = Length::zipCode();
        $this->assertIsArray($options);
        $this->assertSame(5, $options['exact']);

        // Test with real zip code
        $result = $this->validator->validateValue('12345', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('1234', $options);
        $this->assertIsString($result);
    }

    public function testPhoneNumberHelper(): void
    {
        $options = Length::phoneNumber();
        $this->assertIsArray($options);
        $this->assertSame(10, $options['min']);
        $this->assertSame(15, $options['max']);

        // Test with various phone number lengths
        $result = $this->validator->validateValue('1234567890', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('123456789012345', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('123456789', $options);
        $this->assertIsString($result);
    }
}
