<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Positive;
use PHPUnit\Framework\TestCase;

final class PositiveTest extends TestCase
{
    private Positive $validator;

    protected function setUp(): void
    {
        $this->validator = new Positive();
    }

    public function testPositiveNumbers(): void
    {
        $positiveNumbers = [
            1,
            2,
            100,
            1.5,
            0.1,
            999999,
            PHP_INT_MAX,
        ];

        foreach ($positiveNumbers as $number) {
            $result = $this->validator->validateValue($number);
            $this->assertNull($result, "Expected {$number} to be valid positive");
        }
    }

    public function testNegativeNumbers(): void
    {
        $negativeNumbers = [
            -1,
            -2,
            -100,
            -1.5,
            -0.1,
        ];

        foreach ($negativeNumbers as $number) {
            $result = $this->validator->validateValue($number);
            $this->assertIsString($result, "Expected {$number} to be invalid");
        }
    }

    public function testZeroWithoutAllowZero(): void
    {
        $result = $this->validator->validateValue(0);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(0.0);
        $this->assertIsString($result);
    }

    public function testZeroWithAllowZero(): void
    {
        $result = $this->validator->validateValue(0, ['allowZero' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(0.0, ['allowZero' => true]);
        $this->assertNull($result);
    }

    public function testPositiveNumbersWithAllowZero(): void
    {
        $result = $this->validator->validateValue(1, ['allowZero' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(100, ['allowZero' => true]);
        $this->assertNull($result);
    }

    public function testNegativeNumbersWithAllowZero(): void
    {
        $result = $this->validator->validateValue(-1, ['allowZero' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(-0.1, ['allowZero' => true]);
        $this->assertIsString($result);
    }

    public function testNumericStrings(): void
    {
        $result = $this->validator->validateValue('123');
        $this->assertNull($result);

        $result = $this->validator->validateValue('45.67');
        $this->assertNull($result);

        $result = $this->validator->validateValue('-123');
        $this->assertIsString($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonNumericValueReturnsError(): void
    {
        $result = $this->validator->validateValue('not-a-number');
        $this->assertIsString($result);
        $this->assertStringContainsString('numeric', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('numeric', $result);

        $result = $this->validator->validateValue(true);
        $this->assertIsString($result);
        $this->assertStringContainsString('numeric', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom positive error';
        $result = $this->validator->validateValue(-1, ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testDefaultErrorMessage(): void
    {
        $result = $this->validator->validateValue(-1);
        $this->assertSame('Value must be positive', $result);
    }

    public function testAllowZeroHelper(): void
    {
        $options = Positive::allowZero();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowZero', $options);
        $this->assertTrue($options['allowZero']);
    }

    public function testStrictHelper(): void
    {
        $options = Positive::strict();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowZero', $options);
        $this->assertFalse($options['allowZero']);
    }

    public function testFloatingPointNumbers(): void
    {
        $result = $this->validator->validateValue(1.5);
        $this->assertNull($result);

        $result = $this->validator->validateValue(0.001);
        $this->assertNull($result);

        $result = $this->validator->validateValue(-0.001);
        $this->assertIsString($result);
    }

    public function testVerySmallPositiveNumbers(): void
    {
        $result = $this->validator->validateValue(0.0000001);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1e-10);
        $this->assertNull($result);
    }

    public function testVeryLargeNumbers(): void
    {
        $result = $this->validator->validateValue(999999999999);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1e10);
        $this->assertNull($result);
    }

    public function testBoundaryValueZero(): void
    {
        // Default: zero is not allowed
        $result = $this->validator->validateValue(0, ['allowZero' => false]);
        $this->assertIsString($result);

        // With allowZero: zero is allowed
        $result = $this->validator->validateValue(0, ['allowZero' => true]);
        $this->assertNull($result);
    }

    public function testNegativeZero(): void
    {
        // Technically -0.0 === 0.0 in PHP
        $result = $this->validator->validateValue(-0.0);
        $this->assertIsString($result);

        $result = $this->validator->validateValue(-0.0, ['allowZero' => true]);
        $this->assertNull($result);
    }

    public function testIntegerOne(): void
    {
        $result = $this->validator->validateValue(1);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1, ['allowZero' => false]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(1, ['allowZero' => true]);
        $this->assertNull($result);
    }

    public function testEmptyString(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
        $this->assertStringContainsString('numeric', $result);
    }

    public function testWhitespaceString(): void
    {
        $result = $this->validator->validateValue('   ');
        $this->assertIsString($result);
        $this->assertStringContainsString('numeric', $result);
    }

    public function testNumericStringWithSpaces(): void
    {
        // PHP's is_numeric handles leading/trailing spaces
        $result = $this->validator->validateValue(' 123 ');
        $this->assertNull($result);
    }

    public function testScientificNotation(): void
    {
        $result = $this->validator->validateValue('1.5e3');
        $this->assertNull($result);

        $result = $this->validator->validateValue('-1.5e3');
        $this->assertIsString($result);

        $result = $this->validator->validateValue(1.5e3);
        $this->assertNull($result);
    }

    public function testInfinity(): void
    {
        $result = $this->validator->validateValue(INF);
        $this->assertNull($result);

        $result = $this->validator->validateValue(-INF);
        $this->assertIsString($result);
    }
}
