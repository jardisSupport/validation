<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    private PhoneNumber $validator;

    protected function setUp(): void
    {
        $this->validator = new PhoneNumber();
    }

    public function testValidGenericPhoneNumbers(): void
    {
        $validPhones = [
            '1234567890',
            '+12345678901',
            '+491234567890',
            '01234567890',
            '+4401234567890',
        ];

        foreach ($validPhones as $phone) {
            $result = $this->validator->validateValue($phone);
            $this->assertNull($result, "Expected '{$phone}' to be valid");
        }
    }

    public function testInvalidGenericPhoneNumbers(): void
    {
        $invalidPhones = [
            '123',
            'abc',
            '123abc',
            '',
            '12345',
            '+',
            '++1234567890',
            '1234567890123456',
        ];

        foreach ($invalidPhones as $phone) {
            $result = $this->validator->validateValue($phone);
            $this->assertIsString($result, "Expected '{$phone}' to be invalid");
        }
    }

    public function testValidGermanPhoneNumbers(): void
    {
        $validGermanPhones = [
            '+491234567890',
            '00491234567890',
            '01234567890',
        ];

        foreach ($validGermanPhones as $phone) {
            $result = $this->validator->validateValue($phone, ['countryCode' => 'DE']);
            $this->assertNull($result, "Expected '{$phone}' to be valid for Germany");
        }
    }

    public function testInvalidGermanPhoneNumbers(): void
    {
        $invalidGermanPhones = [
            '+11234567890',
            '0012345',
            'abc',
        ];

        foreach ($invalidGermanPhones as $phone) {
            $result = $this->validator->validateValue($phone, ['countryCode' => 'DE']);
            $this->assertIsString($result, "Expected '{$phone}' to be invalid for Germany");
        }
    }

    public function testValidUSPhoneNumbers(): void
    {
        $validUSPhones = [
            '+12025551234',
            '12025551234',
            '2025551234',
        ];

        foreach ($validUSPhones as $phone) {
            $result = $this->validator->validateValue($phone, ['countryCode' => 'US']);
            $this->assertNull($result, "Expected '{$phone}' to be valid for US");
        }
    }

    public function testPhoneNumbersWithFormatting(): void
    {
        // Formatting characters should be stripped
        $formattedPhones = [
            '+1 (202) 555-1234',
            '+49 123 456 7890',
            '123-456-7890',
            '(123) 456-7890',
            '123.456.7890',
        ];

        foreach ($formattedPhones as $phone) {
            $result = $this->validator->validateValue($phone);
            $this->assertNull($result, "Expected '{$phone}' to be valid after formatting removal");
        }
    }

    public function testInternationalFormat(): void
    {
        $result = $this->validator->validateValue('+12025551234', ['requireInternational' => true]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('+491234567890', ['requireInternational' => true]);
        $this->assertNull($result);

        // Without + prefix
        $result = $this->validator->validateValue('12025551234', ['requireInternational' => true]);
        $this->assertIsString($result);
        $this->assertStringContainsString('international', $result);

        // Starting with 0
        $result = $this->validator->validateValue('01234567890', ['requireInternational' => true]);
        $this->assertIsString($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(123456);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom phone error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testUnsupportedCountryCode(): void
    {
        $result = $this->validator->validateValue('+12025551234', ['countryCode' => 'XX']);
        $this->assertIsString($result);
        $this->assertStringContainsString('Unsupported country code', $result);
    }

    public function testCountryCodeCaseInsensitive(): void
    {
        $result = $this->validator->validateValue('+491234567890', ['countryCode' => 'de']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('+491234567890', ['countryCode' => 'DE']);
        $this->assertNull($result);
    }

    public function testGermanHelper(): void
    {
        $options = PhoneNumber::german();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('countryCode', $options);
        $this->assertSame('DE', $options['countryCode']);
    }

    public function testUSHelper(): void
    {
        $options = PhoneNumber::us();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('countryCode', $options);
        $this->assertSame('US', $options['countryCode']);
    }

    public function testInternationalHelper(): void
    {
        $options = PhoneNumber::international();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('requireInternational', $options);
        $this->assertTrue($options['requireInternational']);
    }

    public function testBritishPhoneNumbers(): void
    {
        $validBritishPhones = [
            '+441234567890',
            '00441234567890',
            '01234567890',
        ];

        foreach ($validBritishPhones as $phone) {
            $result = $this->validator->validateValue($phone, ['countryCode' => 'GB']);
            $this->assertNull($result, "Expected '{$phone}' to be valid for GB");
        }
    }

    public function testFrenchPhoneNumbers(): void
    {
        $validFrenchPhones = [
            '+33123456789',
            '0033123456789',
            '0123456789',
        ];

        foreach ($validFrenchPhones as $phone) {
            $result = $this->validator->validateValue($phone, ['countryCode' => 'FR']);
            $this->assertNull($result, "Expected '{$phone}' to be valid for FR");
        }
    }
}
