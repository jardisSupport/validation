<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Json;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    private Json $validator;

    protected function setUp(): void
    {
        $this->validator = new Json();
    }

    public function testValidJson(): void
    {
        $validJson = [
            '{"name":"John","age":30}',
            '["apple","banana","orange"]',
            '{"nested":{"key":"value"}}',
            '[1,2,3,4,5]',
            'true',
            'false',
            'null',
            '123',
            '"string"',
        ];

        foreach ($validJson as $json) {
            $result = $this->validator->validateValue($json);
            $this->assertNull($result, "Expected '{$json}' to be valid JSON");
        }
    }

    public function testInvalidJson(): void
    {
        $invalidJson = [
            '{name:"John"}',
            "{'name':'John'}",
            '{name:John}',
            '{"name":"John",}',
            'undefined',
            '{',
            '}',
            '[',
            ']',
        ];

        foreach ($invalidJson as $json) {
            $result = $this->validator->validateValue($json);
            $this->assertIsString($result, "Expected '{$json}' to be invalid JSON");
        }
    }

    public function testEmptyStringIsInvalid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
    }

    public function testJsonObject(): void
    {
        $result = $this->validator->validateValue('{"key":"value"}', ['expectedType' => 'object']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('["array"]', ['expectedType' => 'object']);
        $this->assertIsString($result);
        $this->assertStringContainsString('object', $result);
    }

    public function testJsonArray(): void
    {
        $result = $this->validator->validateValue('["value1","value2"]', ['expectedType' => 'array']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('[1,2,3]', ['expectedType' => 'array']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('{"key":"value"}', ['expectedType' => 'array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('array', $result);
    }

    public function testNestedJson(): void
    {
        $nested = '{"user":{"name":"John","address":{"city":"New York","zip":"10001"}}}';
        $result = $this->validator->validateValue($nested);
        $this->assertNull($result);
    }

    public function testComplexArray(): void
    {
        $complex = '[{"id":1,"name":"Item 1"},{"id":2,"name":"Item 2"}]';
        $result = $this->validator->validateValue($complex);
        $this->assertNull($result);

        $result = $this->validator->validateValue($complex, ['expectedType' => 'array']);
        $this->assertNull($result);
    }

    public function testJsonWithUnicode(): void
    {
        $unicode = '{"message":"Hello 世界"}';
        $result = $this->validator->validateValue($unicode);
        $this->assertNull($result);
    }

    public function testJsonWithEscapedCharacters(): void
    {
        $escaped = '{"text":"Line 1\\nLine 2\\tTabbed"}';
        $result = $this->validator->validateValue($escaped);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(['already', 'an', 'array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(123);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom JSON error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertStringContainsString('Invalid JSON', $result);
    }

    public function testMaxDepth(): void
    {
        $deeplyNested = '{"a":{"b":{"c":{"d":{"e":"value"}}}}}';

        // Default depth should allow this
        $result = $this->validator->validateValue($deeplyNested);
        $this->assertNull($result);

        // Very shallow depth should fail
        $result = $this->validator->validateValue($deeplyNested, ['maxDepth' => 2]);
        $this->assertIsString($result);
    }

    public function testObjectHelper(): void
    {
        $options = Json::object();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('expectedType', $options);
        $this->assertSame('object', $options['expectedType']);
    }

    public function testArrayHelper(): void
    {
        $options = Json::array();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('expectedType', $options);
        $this->assertSame('array', $options['expectedType']);
    }

    public function testMaxDepthHelper(): void
    {
        $options = Json::maxDepth(10);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('maxDepth', $options);
        $this->assertSame(10, $options['maxDepth']);
    }

    public function testPrimitiveValues(): void
    {
        $primitives = [
            'true',
            'false',
            'null',
            '123',
            '45.67',
            '"string"',
        ];

        foreach ($primitives as $primitive) {
            $result = $this->validator->validateValue($primitive);
            $this->assertNull($result, "Expected '{$primitive}' to be valid JSON");
        }
    }

    public function testEmptyJsonStructures(): void
    {
        $result = $this->validator->validateValue('{}');
        $this->assertNull($result);

        $result = $this->validator->validateValue('[]');
        $this->assertNull($result);

        $result = $this->validator->validateValue('{}', ['expectedType' => 'object']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('[]', ['expectedType' => 'array']);
        $this->assertNull($result);
    }

    public function testJsonErrorMessages(): void
    {
        $result = $this->validator->validateValue('{invalid}');
        $this->assertIsString($result);
        $this->assertStringContainsString('Invalid JSON', $result);
    }

    public function testWhitespaceInJson(): void
    {
        $withWhitespace = <<<JSON
{
    "name": "John",
    "age": 30,
    "city": "New York"
}
JSON;

        $result = $this->validator->validateValue($withWhitespace);
        $this->assertNull($result);
    }

    public function testAssociativeArrayVsIndexedArray(): void
    {
        // Indexed array (sequential keys starting from 0)
        $indexed = '[1,2,3]';
        $result = $this->validator->validateValue($indexed, ['expectedType' => 'array']);
        $this->assertNull($result);

        // Associative array (object in JSON)
        $associative = '{"0":"a","1":"b","2":"c"}';
        $result = $this->validator->validateValue($associative, ['expectedType' => 'object']);
        $this->assertNull($result);
    }

    public function testNumericStringsInJson(): void
    {
        $result = $this->validator->validateValue('{"number":"123"}');
        $this->assertNull($result);

        $result = $this->validator->validateValue('{"number":123}');
        $this->assertNull($result);
    }
}
