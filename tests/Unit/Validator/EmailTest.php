<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    private Email $validator;

    protected function setUp(): void
    {
        $this->validator = new Email();
    }

    public function testValidEmailAddresses(): void
    {
        $validEmails = [
            'user@example.com',
            'test.user@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com',
            'a@b.co',
        ];

        foreach ($validEmails as $email) {
            $result = $this->validator->validateValue($email);
            $this->assertNull($result, "Expected '{$email}' to be valid");
        }
    }

    public function testInvalidEmailAddresses(): void
    {
        $invalidEmails = [
            'not-an-email',
            '@example.com',
            'user@',
            'user @example.com',
            'user@example',
            '',
        ];

        foreach ($invalidEmails as $email) {
            $result = $this->validator->validateValue($email);
            $this->assertIsString($result, "Expected '{$email}' to be invalid");
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
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom email error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testWithDnsCheckHelper(): void
    {
        $options = Email::withDnsCheck();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('checkDns', $options);
        $this->assertTrue($options['checkDns']);
    }

    public function testStrictHelper(): void
    {
        $options = Email::strict();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('strict', $options);
        $this->assertTrue($options['strict']);
    }

    public function testBasicHelper(): void
    {
        $options = Email::basic();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('checkDns', $options);
        $this->assertFalse($options['checkDns']);
        $this->assertArrayHasKey('message', $options);
        $this->assertSame('Invalid email address', $options['message']);
    }

    public function testBasicHelperWithCustomMessage(): void
    {
        $customMessage = 'Please provide a valid email';
        $options = Email::basic($customMessage);
        $this->assertSame($customMessage, $options['message']);
    }

    public function testDnsCheckWithValidDomain(): void
    {
        // Test with a known good domain (gmail.com has MX records)
        $result = $this->validator->validateValue('user@gmail.com', ['checkDns' => true]);
        $this->assertNull($result, 'Expected gmail.com to have valid MX records');
    }

    public function testDnsCheckWithInvalidDomain(): void
    {
        // Test with a domain that doesn't exist
        $result = $this->validator->validateValue('user@thisisaninvaliddomainthatdoesnotexist123456.com', ['checkDns' => true]);
        $this->assertIsString($result);
        $this->assertStringContainsString('domain does not exist', $result);
    }

    public function testDnsCheckWithMissingAtSymbol(): void
    {
        // Edge case: email without @ symbol should fail DNS check
        $result = $this->validator->validateValue('notanemail', ['checkDns' => true]);
        $this->assertIsString($result);
    }
}
