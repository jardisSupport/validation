<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\NotEmpty;
use PHPUnit\Framework\TestCase;

final class NotEmptyTest extends TestCase
{
    private NotEmpty $validator;

    protected function setUp(): void
    {
        $this->validator = new NotEmpty();
    }

    public function testNonEmptyValuesAreValid(): void
    {
        $validValues = [
            'string',
            'test',
            '0',
            0,
            0.0,
            false,
            [1, 2, 3],
            ['key' => 'value'],
        ];

        foreach ($validValues as $value) {
            $result = $this->validator->validateValue($value);
            $this->assertNull($result, "Expected value to be valid: " . var_export($value, true));
        }
    }

    public function testEmptyValuesAreInvalid(): void
    {
        $emptyValues = [
            null,
            '',
            [],
        ];

        foreach ($emptyValues as $value) {
            $result = $this->validator->validateValue($value);
            $this->assertIsString($result, "Expected value to be invalid: " . var_export($value, true));
        }
    }

    public function testNullIsInvalid(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertIsString($result);
        $this->assertStringContainsString('empty', $result);
    }

    public function testEmptyStringIsInvalid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
    }

    public function testEmptyArrayIsInvalid(): void
    {
        $result = $this->validator->validateValue([]);
        $this->assertIsString($result);
    }

    public function testWhitespaceStringWithTrimming(): void
    {
        // Default behavior: trim whitespace
        $result = $this->validator->validateValue('   ');
        $this->assertIsString($result);

        $result = $this->validator->validateValue("\t\n");
        $this->assertIsString($result);

        $result = $this->validator->validateValue('  text  ');
        $this->assertNull($result);
    }

    public function testWhitespaceStringWithoutTrimming(): void
    {
        // Strict mode: don't trim whitespace
        $result = $this->validator->validateValue('   ', ['trimWhitespace' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue("\t\n", ['trimWhitespace' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('', ['trimWhitespace' => false]);
        $this->assertIsString($result);
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

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom empty error';
        $result = $this->validator->validateValue(null, ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertSame('Field must not be empty', $result);
    }

    public function testTrimmedHelper(): void
    {
        $options = NotEmpty::trimmed();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('trimWhitespace', $options);
        $this->assertTrue($options['trimWhitespace']);
    }

    public function testStrictHelper(): void
    {
        $options = NotEmpty::strict();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('trimWhitespace', $options);
        $this->assertFalse($options['trimWhitespace']);
    }

    public function testArrayWithValues(): void
    {
        $result = $this->validator->validateValue([1, 2, 3]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['key' => 'value']);
        $this->assertNull($result);

        $result = $this->validator->validateValue([null]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['']);
        $this->assertNull($result);
    }

    public function testStringWithOnlySpaces(): void
    {
        // With trimming (default)
        $result = $this->validator->validateValue('     ', ['trimWhitespace' => true]);
        $this->assertIsString($result);

        // Without trimming
        $result = $this->validator->validateValue('     ', ['trimWhitespace' => false]);
        $this->assertNull($result);
    }

    public function testMixedWhitespaceCharacters(): void
    {
        $whitespace = " \t\n\r\0\x0B";

        // With trimming (default)
        $result = $this->validator->validateValue($whitespace, ['trimWhitespace' => true]);
        $this->assertIsString($result);

        // Without trimming
        $result = $this->validator->validateValue($whitespace, ['trimWhitespace' => false]);
        $this->assertNull($result);
    }

    public function testNegativeNumbersAreValid(): void
    {
        $result = $this->validator->validateValue(-1);
        $this->assertNull($result);

        $result = $this->validator->validateValue(-0.5);
        $this->assertNull($result);
    }

    public function testObjectsAreValid(): void
    {
        $result = $this->validator->validateValue(new \stdClass());
        $this->assertNull($result);
    }

    public function testDifferenceFromNotBlank(): void
    {
        // NotEmpty checks for empty strings, NotBlank only checks for null
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);

        $result = $this->validator->validateValue([]);
        $this->assertIsString($result);
    }

    public function testSingleSpaceString(): void
    {
        // With trimming
        $result = $this->validator->validateValue(' ');
        $this->assertIsString($result);

        // Without trimming
        $result = $this->validator->validateValue(' ', ['trimWhitespace' => false]);
        $this->assertNull($result);
    }
}
