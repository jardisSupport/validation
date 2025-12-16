<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Ip;
use PHPUnit\Framework\TestCase;

final class IpTest extends TestCase
{
    private Ip $validator;

    protected function setUp(): void
    {
        $this->validator = new Ip();
    }

    public function testValidIpAddresses(): void
    {
        $validIps = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '8.8.8.8',
            '255.255.255.255',
            '0.0.0.0',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '2001:db8:85a3::8a2e:370:7334',
            '::1',
            'fe80::1',
        ];

        foreach ($validIps as $ip) {
            $result = $this->validator->validateValue($ip);
            $this->assertNull($result, "Expected '{$ip}' to be valid");
        }
    }

    public function testInvalidIpAddresses(): void
    {
        $invalidIps = [
            'not-an-ip',
            '256.1.1.1',
            '192.168.1',
            '192.168.1.1.1',
            '',
            'abc.def.ghi.jkl',
            '192.168.-1.1',
        ];

        foreach ($invalidIps as $ip) {
            $result = $this->validator->validateValue($ip);
            $this->assertIsString($result, "Expected '{$ip}' to be invalid");
        }
    }

    public function testValidIpv4Addresses(): void
    {
        $validIpv4 = [
            '192.168.1.1',
            '10.0.0.1',
            '8.8.8.8',
            '255.255.255.255',
            '0.0.0.0',
        ];

        foreach ($validIpv4 as $ip) {
            $result = $this->validator->validateValue($ip, ['version' => 'v4']);
            $this->assertNull($result, "Expected '{$ip}' to be valid IPv4");
        }
    }

    public function testInvalidIpv4Addresses(): void
    {
        // IPv6 addresses should be invalid for IPv4
        $ipv6Address = '2001:db8:85a3::8a2e:370:7334';
        $result = $this->validator->validateValue($ipv6Address, ['version' => 'v4']);
        $this->assertIsString($result);
        $this->assertStringContainsString('IPv4', $result);
    }

    public function testValidIpv6Addresses(): void
    {
        $validIpv6 = [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '2001:db8:85a3::8a2e:370:7334',
            '::1',
            'fe80::1',
            '::',
            '2001:db8::1',
        ];

        foreach ($validIpv6 as $ip) {
            $result = $this->validator->validateValue($ip, ['version' => 'v6']);
            $this->assertNull($result, "Expected '{$ip}' to be valid IPv6");
        }
    }

    public function testInvalidIpv6Addresses(): void
    {
        // IPv4 addresses should be invalid for IPv6
        $ipv4Address = '192.168.1.1';
        $result = $this->validator->validateValue($ipv4Address, ['version' => 'v6']);
        $this->assertIsString($result);
        $this->assertStringContainsString('IPv6', $result);
    }

    public function testPrivateIpAddresses(): void
    {
        $privateIps = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '127.0.0.1',
        ];

        foreach ($privateIps as $ip) {
            // Allow private by default
            $result = $this->validator->validateValue($ip);
            $this->assertNull($result, "Expected '{$ip}' to be valid when private is allowed");

            // Disallow private
            $result = $this->validator->validateValue($ip, ['allowPrivate' => false]);
            $this->assertIsString($result, "Expected '{$ip}' to be invalid when private is not allowed");
        }
    }

    public function testPublicIpAddresses(): void
    {
        $publicIps = [
            '8.8.8.8',
            '1.1.1.1',
            '208.67.222.222',
        ];

        foreach ($publicIps as $ip) {
            $result = $this->validator->validateValue($ip, ['allowPrivate' => false]);
            $this->assertNull($result, "Expected '{$ip}' to be valid public IP");
        }
    }

    public function testLocalhostAddress(): void
    {
        $result = $this->validator->validateValue('127.0.0.1');
        $this->assertNull($result);

        $result = $this->validator->validateValue('127.0.0.1', ['allowPrivate' => false]);
        $this->assertIsString($result);
    }

    public function testLoopbackIpv6(): void
    {
        $result = $this->validator->validateValue('::1', ['version' => 'v6']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('::1', ['version' => 'v6', 'allowPrivate' => false]);
        $this->assertIsString($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(192168);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom IP error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testVersionNumberFormat(): void
    {
        // Test that '4' works as well as 'v4'
        $result = $this->validator->validateValue('192.168.1.1', ['version' => '4']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('::1', ['version' => '6']);
        $this->assertNull($result);
    }

    public function testV4Helper(): void
    {
        $options = Ip::v4();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame('v4', $options['version']);
    }

    public function testV6Helper(): void
    {
        $options = Ip::v6();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame('v6', $options['version']);
    }

    public function testNoPrivateHelper(): void
    {
        $options = Ip::noPrivate();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowPrivate', $options);
        $this->assertFalse($options['allowPrivate']);
    }

    public function testPublicV4Helper(): void
    {
        $options = Ip::publicV4();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('version', $options);
        $this->assertSame('v4', $options['version']);
        $this->assertArrayHasKey('allowPrivate', $options);
        $this->assertFalse($options['allowPrivate']);
    }

    public function testIpv4BoundaryValues(): void
    {
        $result = $this->validator->validateValue('0.0.0.0', ['version' => 'v4']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('255.255.255.255', ['version' => 'v4']);
        $this->assertNull($result);
    }

    public function testIpv6Shorthand(): void
    {
        $result = $this->validator->validateValue('::', ['version' => 'v6']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('::1', ['version' => 'v6']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2001:db8::1', ['version' => 'v6']);
        $this->assertNull($result);
    }

    public function testReservedAddresses(): void
    {
        // Test that reserved addresses are allowed by default
        $reservedIps = [
            '0.0.0.0',
            '240.0.0.1',
        ];

        foreach ($reservedIps as $ip) {
            $result = $this->validator->validateValue($ip);
            $this->assertNull($result, "Expected '{$ip}' to be valid when reserved is allowed");

            $result = $this->validator->validateValue($ip, ['allowReserved' => false]);
            $this->assertIsString($result, "Expected '{$ip}' to be invalid when reserved is not allowed");
        }
    }
}
