<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    private Url $validator;

    protected function setUp(): void
    {
        $this->validator = new Url();
    }

    public function testValidUrls(): void
    {
        $validUrls = [
            'https://www.example.com',
            'http://example.com',
            'https://example.com/path',
            'https://example.com/path?query=value',
            'https://example.com/path?query=value#fragment',
            'https://subdomain.example.com',
            'https://example.com:8080',
            'ftp://example.com',
        ];

        foreach ($validUrls as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertNull($result, "Expected '{$url}' to be valid");
        }
    }

    public function testInvalidUrls(): void
    {
        $invalidUrls = [
            'not-a-url',
            'htp://example.com',
            'example.com',
            '//example.com',
            '',
            'javascript:alert(1)',
        ];

        foreach ($invalidUrls as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertIsString($result, "Expected '{$url}' to be invalid");
        }
    }

    public function testHttpsOnly(): void
    {
        $result = $this->validator->validateValue('https://example.com', ['allowedProtocols' => ['https']]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('http://example.com', ['allowedProtocols' => ['https']]);
        $this->assertIsString($result);
        $this->assertStringContainsString('https', $result);
    }

    public function testHttpAndHttps(): void
    {
        $options = ['allowedProtocols' => ['http', 'https']];

        $result = $this->validator->validateValue('http://example.com', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://example.com', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('ftp://example.com', $options);
        $this->assertIsString($result);
    }

    public function testLocalhostAllowed(): void
    {
        $localhostUrls = [
            'http://localhost',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:3000',
            'http://[::1]',
        ];

        foreach ($localhostUrls as $url) {
            $result = $this->validator->validateValue($url, ['allowLocalhost' => true]);
            $this->assertNull($result, "Expected '{$url}' to be valid when localhost is allowed");
        }
    }

    public function testLocalhostNotAllowed(): void
    {
        $localhostUrls = [
            'http://localhost',
            'http://127.0.0.1',
            'http://[::1]',
        ];

        foreach ($localhostUrls as $url) {
            $result = $this->validator->validateValue($url, ['allowLocalhost' => false]);
            $this->assertIsString($result, "Expected '{$url}' to be invalid when localhost is not allowed");
            $this->assertStringContainsString('Localhost', $result);
        }
    }

    public function testPublicUrls(): void
    {
        $publicUrls = [
            'https://www.google.com',
            'https://www.github.com',
            'https://api.example.com',
        ];

        foreach ($publicUrls as $url) {
            $result = $this->validator->validateValue($url, ['allowLocalhost' => false]);
            $this->assertNull($result, "Expected '{$url}' to be valid public URL");
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
        $customMessage = 'Custom URL error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testHttpsOnlyHelper(): void
    {
        $options = Url::httpsOnly();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowedProtocols', $options);
        $this->assertSame(['https'], $options['allowedProtocols']);
    }

    public function testNoLocalhostHelper(): void
    {
        $options = Url::noLocalhost();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowLocalhost', $options);
        $this->assertFalse($options['allowLocalhost']);
    }

    public function testSecureHelper(): void
    {
        $options = Url::secure();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('allowedProtocols', $options);
        $this->assertSame(['https'], $options['allowedProtocols']);
        $this->assertArrayHasKey('allowLocalhost', $options);
        $this->assertFalse($options['allowLocalhost']);
    }

    public function testUrlsWithQueryParameters(): void
    {
        $result = $this->validator->validateValue('https://example.com?param1=value1&param2=value2');
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://example.com?search=hello%20world');
        $this->assertNull($result);
    }

    public function testUrlsWithFragments(): void
    {
        $result = $this->validator->validateValue('https://example.com#section');
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://example.com/page#section');
        $this->assertNull($result);
    }

    public function testUrlsWithPorts(): void
    {
        $result = $this->validator->validateValue('https://example.com:443');
        $this->assertNull($result);

        $result = $this->validator->validateValue('http://example.com:8080');
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://example.com:3000');
        $this->assertNull($result);
    }

    public function testUrlsWithAuthentication(): void
    {
        $result = $this->validator->validateValue('https://user:pass@example.com');
        $this->assertNull($result);
    }

    public function testUrlsWithSubdomains(): void
    {
        $result = $this->validator->validateValue('https://api.example.com');
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://subdomain.api.example.com');
        $this->assertNull($result);
    }

    public function testUrlsWithDifferentProtocols(): void
    {
        $protocols = [
            'http://example.com',
            'https://example.com',
            'ftp://example.com',
            'ftps://example.com',
        ];

        foreach ($protocols as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertNull($result, "Expected '{$url}' to be valid");
        }
    }

    public function testCaseInsensitiveProtocol(): void
    {
        $result = $this->validator->validateValue('HTTPS://example.com', ['allowedProtocols' => ['https']]);
        $this->assertNull($result);

        $result = $this->validator->validateValue('HTTP://example.com', ['allowedProtocols' => ['http']]);
        $this->assertNull($result);
    }

    public function testUrlsWithPaths(): void
    {
        $result = $this->validator->validateValue('https://example.com/path/to/resource');
        $this->assertNull($result);

        $result = $this->validator->validateValue('https://example.com/path/to/resource.html');
        $this->assertNull($result);
    }

    public function testInternationalizedDomainNames(): void
    {
        // IDN (Internationalized Domain Names)
        $result = $this->validator->validateValue('https://xn--e1afmkfd.xn--p1ai');
        $this->assertNull($result);
    }

    public function testEmptyStringIsInvalid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
    }

    public function testWhitespaceIsInvalid(): void
    {
        $result = $this->validator->validateValue('   ');
        $this->assertIsString($result);
    }

    public function testUrlWithSpaces(): void
    {
        $result = $this->validator->validateValue('https://example.com/path with spaces');
        $this->assertIsString($result);
    }

    public function testDangerousProtocols(): void
    {
        $dangerousUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            'file:///etc/passwd',
        ];

        foreach ($dangerousUrls as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertIsString($result, "Expected dangerous URL '{$url}' to be invalid");
        }
    }

    public function testLocalhost127Network(): void
    {
        $result = $this->validator->validateValue('http://127.0.0.5', ['allowLocalhost' => false]);
        $this->assertIsString($result);
        $this->assertStringContainsString('Localhost', $result);
    }

    public function testSecureHelperCombination(): void
    {
        $options = Url::secure();

        // HTTPS should be valid
        $result = $this->validator->validateValue('https://example.com', $options);
        $this->assertNull($result);

        // HTTP should be invalid
        $result = $this->validator->validateValue('http://example.com', $options);
        $this->assertIsString($result);

        // Localhost should be invalid
        $result = $this->validator->validateValue('https://localhost', $options);
        $this->assertIsString($result);
    }

    public function testUncommonProtocolIsRejected(): void
    {
        // Test that uncommon protocols are rejected when no allowedProtocols is specified
        $uncommonProtocols = [
            'gopher://example.com',
            'telnet://example.com',
            'ldap://example.com',
        ];

        foreach ($uncommonProtocols as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertIsString($result, "Expected uncommon protocol URL '{$url}' to be invalid");
        }
    }

    public function testCommonProtocolsAreAccepted(): void
    {
        // Test that common protocols are accepted when no allowedProtocols is specified
        $commonProtocols = [
            'http://example.com',
            'https://example.com',
            'ftp://example.com',
            'ftps://example.com',
            'ssh://example.com',
            'sftp://example.com',
            'ws://example.com',
            'wss://example.com',
        ];

        foreach ($commonProtocols as $url) {
            $result = $this->validator->validateValue($url);
            $this->assertNull($result, "Expected common protocol URL '{$url}' to be valid");
        }
    }
}
