<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\UniqueItems;
use PHPUnit\Framework\TestCase;

final class UniqueItemsTest extends TestCase
{
    private UniqueItems $validator;

    protected function setUp(): void
    {
        $this->validator = new UniqueItems();
    }

    public function testArrayWithUniqueItems(): void
    {
        $uniqueArrays = [
            [1, 2, 3, 4, 5],
            ['a', 'b', 'c'],
            ['apple', 'banana', 'orange'],
            [1, '1', true],
        ];

        foreach ($uniqueArrays as $array) {
            $result = $this->validator->validateValue($array);
            $this->assertNull($result, "Expected array to have unique items: " . json_encode($array));
        }
    }

    public function testArrayWithDuplicateItems(): void
    {
        $duplicateArrays = [
            [1, 2, 3, 2],
            ['a', 'b', 'a'],
            ['test', 'test'],
            [1, 2, 3, 4, 5, 1],
        ];

        foreach ($duplicateArrays as $array) {
            $result = $this->validator->validateValue($array);
            $this->assertIsString($result, "Expected array to have duplicates: " . json_encode($array));
        }
    }

    public function testStrictComparison(): void
    {
        // Strict comparison (default): 1 and '1' are different
        $result = $this->validator->validateValue([1, '1'], ['strict' => true]);
        $this->assertNull($result);

        // Same values with strict comparison
        $result = $this->validator->validateValue([1, 1], ['strict' => true]);
        $this->assertIsString($result);
    }

    public function testLooseComparison(): void
    {
        // Loose comparison: 1 and '1' are the same
        $result = $this->validator->validateValue([1, '1'], ['strict' => false]);
        $this->assertIsString($result);

        // Different values with loose comparison
        $result = $this->validator->validateValue([1, 2], ['strict' => false]);
        $this->assertNull($result);
    }

    public function testEmptyArrayIsValid(): void
    {
        $result = $this->validator->validateValue([]);
        $this->assertNull($result);
    }

    public function testSingleItemArrayIsValid(): void
    {
        $result = $this->validator->validateValue([1]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['test']);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonArrayValueReturnsError(): void
    {
        $result = $this->validator->validateValue('not-an-array');
        $this->assertIsString($result);
        $this->assertStringContainsString('array', $result);

        $result = $this->validator->validateValue(123);
        $this->assertIsString($result);
        $this->assertStringContainsString('array', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom unique error';
        $result = $this->validator->validateValue([1, 2, 1], ['message' => $customMessage]);
        $this->assertStringContainsString($customMessage, $result);
    }

    public function testDuplicatesAreListedInError(): void
    {
        $result = $this->validator->validateValue([1, 2, 3, 2, 4, 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('duplicates', $result);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('3', $result);
    }

    public function testStrictHelper(): void
    {
        $options = UniqueItems::strict();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('strict', $options);
        $this->assertTrue($options['strict']);
    }

    public function testLooseHelper(): void
    {
        $options = UniqueItems::loose();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('strict', $options);
        $this->assertFalse($options['strict']);
    }

    public function testNumericStringComparison(): void
    {
        // Strict: '1' and 1 are different
        $result = $this->validator->validateValue(['1', 1], ['strict' => true]);
        $this->assertNull($result);

        // Loose: '1' and 1 are the same
        $result = $this->validator->validateValue(['1', 1], ['strict' => false]);
        $this->assertIsString($result);
    }

    public function testBooleanComparison(): void
    {
        // Strict: true and 1 are different
        $result = $this->validator->validateValue([true, 1], ['strict' => true]);
        $this->assertNull($result);

        // Loose: true and 1 are the same
        $result = $this->validator->validateValue([true, 1], ['strict' => false]);
        $this->assertIsString($result);
    }

    public function testNestedArrays(): void
    {
        // Strict comparison of nested arrays
        $result = $this->validator->validateValue([[1, 2], [3, 4]], ['strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([[1, 2], [1, 2]], ['strict' => true]);
        $this->assertIsString($result);
    }

    public function testMixedTypes(): void
    {
        // With strict comparison, 1 (int), '1' (string), 1.0 (float), true (bool) are all different types
        $result = $this->validator->validateValue([1, '1', 1.0, true], ['strict' => true]);
        $this->assertNull($result);

        // But with duplicates of same type, it should fail
        $result = $this->validator->validateValue([1, 2, 1], ['strict' => true]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue([1, '2', 3.0, false], ['strict' => true]);
        $this->assertNull($result);
    }

    public function testZeroValues(): void
    {
        $result = $this->validator->validateValue([0, '0'], ['strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([0, '0'], ['strict' => false]);
        $this->assertIsString($result);

        $result = $this->validator->validateValue([0, 0], ['strict' => true]);
        $this->assertIsString($result);
    }

    public function testNullInArray(): void
    {
        $result = $this->validator->validateValue([null, 1, 2], ['strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue([null, null], ['strict' => true]);
        $this->assertIsString($result);
    }

    public function testEmptyStringInArray(): void
    {
        $result = $this->validator->validateValue(['', 'test'], ['strict' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['', ''], ['strict' => true]);
        $this->assertIsString($result);
    }

    public function testMultipleDuplicates(): void
    {
        $result = $this->validator->validateValue([1, 1, 1, 2, 2, 3]);
        $this->assertIsString($result);
    }

    public function testAssociativeArray(): void
    {
        // Only values are checked, not keys
        $result = $this->validator->validateValue(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertNull($result);

        $result = $this->validator->validateValue(['a' => 1, 'b' => 2, 'c' => 1]);
        $this->assertIsString($result);
    }

    public function testLargeArray(): void
    {
        $largeUniqueArray = range(1, 1000);
        $result = $this->validator->validateValue($largeUniqueArray);
        $this->assertNull($result);

        $largeDuplicateArray = array_merge(range(1, 1000), [500]);
        $result = $this->validator->validateValue($largeDuplicateArray);
        $this->assertIsString($result);
    }
}
