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

    public function testStrictModeRejectsQuotedLocalPart(): void
    {
        $options = Email::strict();

        // Quoted local parts should be rejected in strict mode
        $result = $this->validator->validateValue('"user name"@example.com', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('quoted', strtolower($result));
    }

    public function testStrictModeRejectsIpLiteral(): void
    {
        $options = Email::strict();

        // IP literal domains should be rejected in strict mode
        $result = $this->validator->validateValue('user@[192.168.1.1]', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('IP literal', $result);
    }

    public function testStrictModeAcceptsNormalEmail(): void
    {
        $options = Email::strict();

        // Normal emails should pass strict mode
        $result = $this->validator->validateValue('user@example.com', $options);
        $this->assertNull($result);
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

    public function testCustomMessageIsRespectedForAllErrors(): void
    {
        $custom = 'Custom email error for all cases';

        // Quoted string in strict mode
        $result = $this->validator->validateValue(
            '"user"@example.com',
            ['message' => $custom, 'strict' => true]
        );
        $this->assertSame($custom, $result);

        // IP literal in strict mode
        $result = $this->validator->validateValue(
            'user@[192.168.1.1]',
            ['message' => $custom, 'strict' => true]
        );
        $this->assertSame($custom, $result);

        // Domain without dot in strict mode
        $result = $this->validator->validateValue(
            'user@localhost',
            ['message' => $custom, 'strict' => true]
        );
        $this->assertSame($custom, $result);

        // Basic invalid email
        $result = $this->validator->validateValue('invalid', ['message' => $custom]);
        $this->assertSame($custom, $result);

        // DNS check with invalid domain
        $result = $this->validator->validateValue(
            'user@thisisaninvaliddomainthatdoesnotexist123456.com',
            ['message' => $custom, 'checkDns' => true]
        );
        $this->assertSame($custom, $result);
    }

    public function testDnsCheckWithMissingAtSymbol(): void
    {
        // Edge case: email without @ symbol should fail DNS check
        $result = $this->validator->validateValue('notanemail', ['checkDns' => true]);
        $this->assertIsString($result);
    }
}
