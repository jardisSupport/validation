<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Range;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    private Range $validator;

    protected function setUp(): void
    {
        $this->validator = new Range();
    }

    public function testValidRangeWithMinAndMax(): void
    {
        $result = $this->validator->validateValue(5, ['min' => 1, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testValueBelowMinimum(): void
    {
        $result = $this->validator->validateValue(0, ['min' => 1, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('1', $result);
    }

    public function testValueAboveMaximum(): void
    {
        $result = $this->validator->validateValue(11, ['min' => 1, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('10', $result);
    }

    public function testMinimumOnly(): void
    {
        $result = $this->validator->validateValue(100, ['min' => 10]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(5, ['min' => 10]);
        $this->assertIsString($result);
    }

    public function testMaximumOnly(): void
    {
        $result = $this->validator->validateValue(5, ['max' => 10]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(15, ['max' => 10]);
        $this->assertIsString($result);
    }

    public function testBoundaryValues(): void
    {
        $result = $this->validator->validateValue(1, ['min' => 1, 'max' => 10]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(10, ['min' => 1, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null, ['min' => 1, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testStringLengthValidation(): void
    {
        // Valid string length within range
        $result = $this->validator->validateValue('hello', ['min' => 3, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testStringLengthTooShort(): void
    {
        $result = $this->validator->validateValue('hi', ['min' => 3, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('too short', strtolower($result));
    }

    public function testStringLengthTooLong(): void
    {
        $result = $this->validator->validateValue('this is a very long string', ['min' => 3, 'max' => 10]);
        $this->assertIsString($result);
        $this->assertStringContainsString('too long', strtolower($result));
    }

    public function testNonNumericNonStringValueReturnsNull(): void
    {
        // For arrays, objects, etc. the validator returns null
        $result = $this->validator->validateValue([], ['min' => 1, 'max' => 10]);
        $this->assertNull($result);
    }

    public function testFloatValues(): void
    {
        $result = $this->validator->validateValue(5.5, ['min' => 1.0, 'max' => 10.0]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(0.5, ['min' => 1.0, 'max' => 10.0]);
        $this->assertIsString($result);
    }

    public function testBetweenHelper(): void
    {
        $options = Range::between(18, 120);
        $this->assertIsArray($options);
        $this->assertSame(18, $options['min']);
        $this->assertSame(120, $options['max']);
    }

    public function testMinHelper(): void
    {
        $options = Range::min(0);
        $this->assertIsArray($options);
        $this->assertSame(0, $options['min']);
        $this->assertArrayNotHasKey('max', $options);
    }

    public function testMaxHelper(): void
    {
        $options = Range::max(100);
        $this->assertIsArray($options);
        $this->assertSame(100, $options['max']);
        $this->assertArrayNotHasKey('min', $options);
    }

    public function testPositiveHelper(): void
    {
        $options = Range::positive();
        $this->assertIsArray($options);
        $this->assertSame(0, $options['min']);
    }
}
