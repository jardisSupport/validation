<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Alphanumeric;
use PHPUnit\Framework\TestCase;

final class AlphanumericTest extends TestCase
{
    private Alphanumeric $validator;

    protected function setUp(): void
    {
        $this->validator = new Alphanumeric();
    }

    public function testValidAlphanumericStrings(): void
    {
        $validStrings = [
            'abc123',
            'test',
            '12345',
            'ABC',
            'Test123',
            'a1b2c3',
        ];

        foreach ($validStrings as $string) {
            $result = $this->validator->validateValue($string);
            $this->assertNull($result, "Expected '{$string}' to be valid");
        }
    }

    public function testInvalidAlphanumericStrings(): void
    {
        $invalidStrings = [
            'test@example',
            'hello world',
            'test-123',
            'test_123',
            'hello!',
            'test#123',
        ];

        foreach ($invalidStrings as $string) {
            $result = $this->validator->validateValue($string);
            $this->assertIsString($result, "Expected '{$string}' to be invalid");
        }
    }

    public function testEmptyStringIsValid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertNull($result);
    }

    public function testWithSpaces(): void
    {
        $result = $this->validator->validateValue('hello world', ['allowSpaces' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('test 123', ['allowSpaces' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('hello world', ['allowSpaces' => false]);
        $this->assertIsString($result);
    }

    public function testWithDashes(): void
    {
        $result = $this->validator->validateValue('test-123', ['additionalChars' => '-']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('hello-world-123', ['additionalChars' => '-']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('test-123');
        $this->assertIsString($result);
    }

    public function testWithUnderscores(): void
    {
        $result = $this->validator->validateValue('test_123', ['additionalChars' => '_']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('hello_world_123', ['additionalChars' => '_']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('test_123');
        $this->assertIsString($result);
    }

    public function testWithMultipleAdditionalChars(): void
    {
        $result = $this->validator->validateValue('test-123_abc', ['additionalChars' => '-_']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('hello-world_123', ['additionalChars' => '-_']);
        $this->assertNull($result);
    }

    public function testWithSpacesAndDashes(): void
    {
        $result = $this->validator->validateValue('hello world-123', ['allowSpaces' => true, 'additionalChars' => '-']);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(123);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom alphanumeric error';
        $result = $this->validator->validateValue('test@', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testCaseSensitivity(): void
    {
        $result = $this->validator->validateValue('ABC');
        $this->assertNull($result);

        $result = $this->validator->validateValue('abc');
        $this->assertNull($result);

        $result = $this->validator->validateValue('AbC123');
        $this->assertNull($result);
    }

    public function testSpecialCharacters(): void
    {
        $specialChars = [
            '@',
            '#',
            '$',
            '%',
            '&',
            '*',
            '(',
            ')',
            '+',
            '=',
            '[',
            ']',
            '{',
            '}',
            '|',
            '\\',
            '/',
            '<',
            '>',
            '?',
            '!',
            '~',
            '`',
        ];

        foreach ($specialChars as $char) {
            $result = $this->validator->validateValue("test{$char}123");
            $this->assertIsString($result, "Expected 'test{$char}123' to be invalid");
        }
    }

    public function testWithDashesHelper(): void
    {
        $options = Alphanumeric::withDashes();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('additionalChars', $options);
        $this->assertSame('-', $options['additionalChars']);
    }

    public function testWithSpacesHelper(): void
    {
        $options = Alphanumeric::withSpaces();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowSpaces', $options);
        $this->assertTrue($options['allowSpaces']);
    }

    public function testWithUnderscoresHelper(): void
    {
        $options = Alphanumeric::withUnderscores();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('additionalChars', $options);
        $this->assertSame('_', $options['additionalChars']);
    }

    public function testNumericOnly(): void
    {
        $result = $this->validator->validateValue('12345');
        $this->assertNull($result);

        $result = $this->validator->validateValue('0');
        $this->assertNull($result);
    }

    public function testAlphaOnly(): void
    {
        $result = $this->validator->validateValue('abcdef');
        $this->assertNull($result);

        $result = $this->validator->validateValue('ABCDEF');
        $this->assertNull($result);
    }

    public function testUnicodeCharacters(): void
    {
        // Non-ASCII characters should be invalid
        $result = $this->validator->validateValue('hello世界');
        $this->assertIsString($result);

        $result = $this->validator->validateValue('café');
        $this->assertIsString($result);
    }

    public function testWhitespaceWithoutAllowSpaces(): void
    {
        $result = $this->validator->validateValue('test test');
        $this->assertIsString($result);

        $result = $this->validator->validateValue("test\ttest");
        $this->assertIsString($result);

        $result = $this->validator->validateValue("test\ntest");
        $this->assertIsString($result);
    }

    public function testWhitespaceWithAllowSpaces(): void
    {
        $result = $this->validator->validateValue('test test', ['allowSpaces' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue("test\ttest", ['allowSpaces' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue("test\ntest", ['allowSpaces' => true]);
        $this->assertNull($result);
    }
}
