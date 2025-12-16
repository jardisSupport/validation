<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Count;
use PHPUnit\Framework\TestCase;

final class CountTest extends TestCase
{
    private Count $validator;

    protected function setUp(): void
    {
        $this->validator = new Count();
    }

    public function testMinimumCount(): void
    {
        $result = $this->validator->validateValue([1, 2, 3, 4, 5], ['min' => 3]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2], ['min' => 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('3', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function testMaximumCount(): void
    {
        $result = $this->validator->validateValue([1, 2, 3], ['max' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2, 3, 4, 5, 6], ['max' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('6', $result);
    }

    public function testExactCount(): void
    {
        $result = $this->validator->validateValue([1, 2, 3], ['exact' => 3]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2], ['exact' => 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('3', $result);
        $this->assertStringContainsString('2', $result);

        $result = $this->validator->validateValue([1, 2, 3, 4], ['exact' => 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('3', $result);
        $this->assertStringContainsString('4', $result);
    }

    public function testBetweenMinAndMax(): void
    {
        $result = $this->validator->validateValue([1, 2, 3, 4], ['min' => 2, 'max' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1], ['min' => 2, 'max' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('2', $result);

        $result = $this->validator->validateValue([1, 2, 3, 4, 5, 6], ['min' => 2, 'max' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('5', $result);
    }

    public function testBoundaryValues(): void
    {
        $result = $this->validator->validateValue([1, 2, 3], ['min' => 3, 'max' => 5]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2, 3, 4, 5], ['min' => 3, 'max' => 5]);
        $this->assertNull($result);
    }

    public function testEmptyArray(): void
    {
        $result = $this->validator->validateValue([], ['min' => 1]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue([], ['exact' => 0]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([], ['max' => 5]);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null, ['min' => 1, 'max' => 5]);
        $this->assertNull($result);
    }

    public function testNonArrayValueReturnsError(): void
    {
        $result = $this->validator->validateValue('not-an-array', ['min' => 1]);
        $this->assertIsString($result);
        $this->assertStringContainsString('array', $result);

        $result = $this->validator->validateValue(123, ['min' => 1]);
        $this->assertIsString($result);
        $this->assertStringContainsString('array', $result);
    }

    public function testCountableObject(): void
    {
        $countable = new \ArrayObject([1, 2, 3]);
        $result = $this->validator->validateValue($countable, ['min' => 2, 'max' => 4]);
        $this->assertNull($result);

        $result = $this->validator->validateValue($countable, ['exact' => 3]);
        $this->assertNull($result);
    }

    public function testMinHelper(): void
    {
        $options = Count::min(5);
        $this->assertIsArray($options);
        $this->assertSame(5, $options['min']);
        $this->assertArrayNotHasKey('max', $options);
    }

    public function testMaxHelper(): void
    {
        $options = Count::max(10);
        $this->assertIsArray($options);
        $this->assertSame(10, $options['max']);
        $this->assertArrayNotHasKey('min', $options);
    }

    public function testExactHelper(): void
    {
        $options = Count::exact(3);
        $this->assertIsArray($options);
        $this->assertSame(3, $options['exact']);
    }

    public function testBetweenHelper(): void
    {
        $options = Count::between(5, 10);
        $this->assertIsArray($options);
        $this->assertSame(5, $options['min']);
        $this->assertSame(10, $options['max']);
    }

    public function testExceptionWhenNoOptionsProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of min, max, or exact must be specified');
        $this->validator->validateValue([1, 2, 3], []);
    }

    public function testExceptionWhenExactAndMinProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify exact with min or max');
        $this->validator->validateValue([1, 2, 3], ['exact' => 3, 'min' => 2]);
    }

    public function testExceptionWhenExactAndMaxProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify exact with min or max');
        $this->validator->validateValue([1, 2, 3], ['exact' => 3, 'max' => 5]);
    }

    public function testAssociativeArray(): void
    {
        $result = $this->validator->validateValue(['a' => 1, 'b' => 2, 'c' => 3], ['exact' => 3]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['key' => 'value'], ['min' => 1, 'max' => 5]);
        $this->assertNull($result);
    }

    public function testLargeArray(): void
    {
        $largeArray = range(1, 1000);
        $result = $this->validator->validateValue($largeArray, ['min' => 500, 'max' => 1500]);
        $this->assertNull($result);

        $result = $this->validator->validateValue($largeArray, ['exact' => 1000]);
        $this->assertNull($result);
    }

    public function testZeroCount(): void
    {
        $result = $this->validator->validateValue([], ['exact' => 0]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1], ['exact' => 0]);
        $this->assertIsString($result);
    }

    public function testMinimumOfZero(): void
    {
        $result = $this->validator->validateValue([], ['min' => 0]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([1, 2, 3], ['min' => 0]);
        $this->assertNull($result);
    }

    public function testSingularAndPluralInMessages(): void
    {
        $result = $this->validator->validateValue([1], ['exact' => 2]);
        $this->assertIsString($result);
        $this->assertStringContainsString('element(s)', $result);
    }

    public function testArrayWithNullValues(): void
    {
        $result = $this->validator->validateValue([null, null, null], ['exact' => 3]);
        $this->assertNull($result);
    }

    public function testNestedArrays(): void
    {
        $nested = [[1, 2], [3, 4], [5, 6]];
        $result = $this->validator->validateValue($nested, ['exact' => 3]);
        $this->assertNull($result);
    }
}
