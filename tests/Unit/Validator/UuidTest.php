<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private Uuid $validator;

    protected function setUp(): void
    {
        $this->validator = new Uuid();
    }

    public function testValidUuids(): void
    {
        $validUuids = [
            '550e8400-e29b-41d4-a716-446655440000',
            '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
            'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            '123e4567-e89b-12d3-a456-426614174000',
        ];

        foreach ($validUuids as $uuid) {
            $result = $this->validator->validateValue($uuid);
            $this->assertNull($result, "Expected '{$uuid}' to be valid");
        }
    }

    public function testInvalidUuids(): void
    {
        $invalidUuids = [
            'not-a-uuid',
            '550e8400-e29b-41d4-a716',
            '550e8400-e29b-41d4-a716-446655440000-extra',
            '550e8400e29b41d4a716446655440000',
            'zzzzzzzz-zzzz-zzzz-zzzz-zzzzzzzzzzzz',
            '',
            '550e8400-e29b-41d4-a716-44665544000',
            '550e8400-e29b-41d4-a716-4466554400000',
        ];

        foreach ($invalidUuids as $uuid) {
            $result = $this->validator->validateValue($uuid);
            $this->assertIsString($result, "Expected '{$uuid}' to be invalid");
        }
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
        $customMessage = 'Custom UUID error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testUuidVersion1(): void
    {
        // Version 1 UUID (time-based)
        $uuidV1 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $result = $this->validator->validateValue($uuidV1, ['version' => 1]);
        $this->assertNull($result);

        // Not version 1
        $uuidV4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $result = $this->validator->validateValue($uuidV4, ['version' => 1]);
        $this->assertIsString($result);
        $this->assertStringContainsString('version 1', $result);
    }

    public function testUuidVersion3(): void
    {
        // Version 3 UUID (MD5 hash)
        $uuidV3 = '6ba7b810-9dad-31d1-80b4-00c04fd430c8';
        $result = $this->validator->validateValue($uuidV3, ['version' => 3]);
        $this->assertNull($result);

        // Not version 3
        $uuidV4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $result = $this->validator->validateValue($uuidV4, ['version' => 3]);
        $this->assertIsString($result);
        $this->assertStringContainsString('version 3', $result);
    }

    public function testUuidVersion4(): void
    {
        // Version 4 UUID (random)
        $uuidV4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $result = $this->validator->validateValue($uuidV4, ['version' => 4]);
        $this->assertNull($result);

        // Not version 4
        $uuidV1 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $result = $this->validator->validateValue($uuidV1, ['version' => 4]);
        $this->assertIsString($result);
        $this->assertStringContainsString('version 4', $result);
    }

    public function testUuidVersion5(): void
    {
        // Version 5 UUID (SHA-1 hash)
        $uuidV5 = '6ba7b810-9dad-51d1-80b4-00c04fd430c8';
        $result = $this->validator->validateValue($uuidV5, ['version' => 5]);
        $this->assertNull($result);

        // Not version 5
        $uuidV4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $result = $this->validator->validateValue($uuidV4, ['version' => 5]);
        $this->assertIsString($result);
        $this->assertStringContainsString('version 5', $result);
    }

    public function testCaseInsensitive(): void
    {
        $uuidLower = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $uuidUpper = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $uuidMixed = 'F47Ac10b-58cC-4372-A567-0e02b2c3d479';

        $result = $this->validator->validateValue($uuidLower);
        $this->assertNull($result);

        $result = $this->validator->validateValue($uuidUpper);
        $this->assertNull($result);

        $result = $this->validator->validateValue($uuidMixed);
        $this->assertNull($result);
    }

    public function testAnyHelper(): void
    {
        $options = Uuid::any();
        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    public function testV1Helper(): void
    {
        $options = Uuid::v1();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame(1, $options['version']);
    }

    public function testV3Helper(): void
    {
        $options = Uuid::v3();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame(3, $options['version']);
    }

    public function testV4Helper(): void
    {
        $options = Uuid::v4();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame(4, $options['version']);
    }

    public function testV5Helper(): void
    {
        $options = Uuid::v5();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame(5, $options['version']);
    }

    public function testInvalidFormat(): void
    {
        $invalidFormats = [
            '550e8400e29b41d4a716446655440000',
            '550e8400-e29b-41d4-a716-44665544-0000',
            '550e8400-e29b-41d4-a716446655440000',
        ];

        foreach ($invalidFormats as $format) {
            $result = $this->validator->validateValue($format);
            $this->assertIsString($result, "Expected '{$format}' to be invalid");
        }
    }

    public function testNilUuid(): void
    {
        $nilUuid = '00000000-0000-0000-0000-000000000000';
        $result = $this->validator->validateValue($nilUuid);
        $this->assertNull($result);
    }
}
