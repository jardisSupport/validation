<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Format;
use PHPUnit\Framework\TestCase;

final class FormatTest extends TestCase
{
    private Format $validator;

    protected function setUp(): void
    {
        $this->validator = new Format();
    }

    public function testValidPatternMatch(): void
    {
        $result = $this->validator->validateValue('test123', ['pattern' => '/^[a-z0-9]+$/']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('ABC', ['pattern' => '/^[A-Z]+$/']);
        $this->assertNull($result);
    }

    public function testInvalidPatternMatch(): void
    {
        $result = $this->validator->validateValue('test@123', ['pattern' => '/^[a-z0-9]+$/']);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('abc', ['pattern' => '/^[A-Z]+$/']);
        $this->assertIsString($result);
    }

    public function testEmailPattern(): void
    {
        $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        $result = $this->validator->validateValue('test@example.com', ['pattern' => $emailPattern]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('invalid-email', ['pattern' => $emailPattern]);
        $this->assertIsString($result);
    }

    public function testNumericPattern(): void
    {
        $result = $this->validator->validateValue('12345', ['pattern' => '/^\d+$/']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('123abc', ['pattern' => '/^\d+$/']);
        $this->assertIsString($result);
    }

    public function testAlphabeticPattern(): void
    {
        $result = $this->validator->validateValue('abcdef', ['pattern' => '/^[a-zA-Z]+$/']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('abc123', ['pattern' => '/^[a-zA-Z]+$/']);
        $this->assertIsString($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null, ['pattern' => '/^test$/']);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(123, ['pattern' => '/^\d+$/']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array'], ['pattern' => '/^test$/']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom format error';
        $result = $this->validator->validateValue('invalid', ['pattern' => '/^valid$/', 'message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue('invalid', ['pattern' => '/^valid$/']);
        $this->assertSame('Value is not in correct format', $result);
    }

    public function testExceptionWhenNoPatternProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pattern must be specified');
        $this->validator->validateValue('test', []);
    }

    public function testPatternHelper(): void
    {
        $pattern = '/^[a-z]+$/';
        $options = Format::pattern($pattern);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('pattern', $options);
        $this->assertSame($pattern, $options['pattern']);
    }

    public function testSlugHelper(): void
    {
        $options = Format::slug();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('pattern', $options);
        $this->assertSame('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $options['pattern']);
    }

    public function testSlugHelperWithValidValues(): void
    {
        $options = Format::slug();

        $validSlugs = [
            'hello-world',
            'test-123',
            'my-slug',
            'a',
            '123',
        ];

        foreach ($validSlugs as $slug) {
            $result = $this->validator->validateValue($slug, $options);
            $this->assertNull($result, "Expected '{$slug}' to be valid slug");
        }
    }

    public function testSlugHelperWithInvalidValues(): void
    {
        $options = Format::slug();

        $invalidSlugs = [
            'Hello-World',
            'test_123',
            'my slug',
            '-start',
            'end-',
            'double--dash',
            'test@slug',
        ];

        foreach ($invalidSlugs as $slug) {
            $result = $this->validator->validateValue($slug, $options);
            $this->assertIsString($result, "Expected '{$slug}' to be invalid slug");
        }
    }

    public function testHexColorHelper(): void
    {
        $options = Format::hexColor();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('pattern', $options);
        $this->assertSame('/^#[0-9A-Fa-f]{6}$/', $options['pattern']);
    }

    public function testHexColorHelperWithValidValues(): void
    {
        $options = Format::hexColor();

        $validColors = [
            '#FFFFFF',
            '#000000',
            '#FF5733',
            '#123abc',
            '#AbCdEf',
        ];

        foreach ($validColors as $color) {
            $result = $this->validator->validateValue($color, $options);
            $this->assertNull($result, "Expected '{$color}' to be valid hex color");
        }
    }

    public function testHexColorHelperWithInvalidValues(): void
    {
        $options = Format::hexColor();

        $invalidColors = [
            'FFFFFF',
            '#FFF',
            '#GGGGGG',
            '#12345',
            '#1234567',
            'red',
        ];

        foreach ($invalidColors as $color) {
            $result = $this->validator->validateValue($color, $options);
            $this->assertIsString($result, "Expected '{$color}' to be invalid hex color");
        }
    }

    public function testComplexPattern(): void
    {
        // Password pattern: at least 8 chars, 1 uppercase, 1 lowercase, 1 digit
        $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';

        $result = $this->validator->validateValue('Password123', ['pattern' => $passwordPattern]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('password', ['pattern' => $passwordPattern]);
        $this->assertIsString($result);
    }

    public function testPhoneNumberPattern(): void
    {
        $phonePattern = '/^\+?[1-9]\d{6,14}$/';

        $result = $this->validator->validateValue('+12025551234', ['pattern' => $phonePattern]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('123', ['pattern' => $phonePattern]);
        $this->assertIsString($result);
    }

    public function testDatePattern(): void
    {
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

        $result = $this->validator->validateValue('2024-01-15', ['pattern' => $datePattern]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2024-1-15', ['pattern' => $datePattern]);
        $this->assertIsString($result);
    }

    public function testUuidPattern(): void
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        $result = $this->validator->validateValue('550e8400-e29b-41d4-a716-446655440000', ['pattern' => $uuidPattern]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('not-a-uuid', ['pattern' => $uuidPattern]);
        $this->assertIsString($result);
    }

    public function testEmptyStringWithPattern(): void
    {
        $result = $this->validator->validateValue('', ['pattern' => '/^.+$/']);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('', ['pattern' => '/^.*$/']);
        $this->assertNull($result);
    }

    public function testCaseInsensitivePattern(): void
    {
        $result = $this->validator->validateValue('TEST', ['pattern' => '/^test$/i']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('TeSt', ['pattern' => '/^test$/i']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('TEST', ['pattern' => '/^test$/']);
        $this->assertIsString($result);
    }

    public function testMultilinePattern(): void
    {
        $multiline = "line1\nline2\nline3";

        $result = $this->validator->validateValue($multiline, ['pattern' => '/^line1.*line3$/s']);
        $this->assertNull($result);
    }

    public function testSpecialCharactersInPattern(): void
    {
        $result = $this->validator->validateValue('test@example.com', ['pattern' => '/^[\w\.-]+@[\w\.-]+\.\w+$/']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('$100.00', ['pattern' => '/^\$\d+\.\d{2}$/']);
        $this->assertNull($result);
    }
}
