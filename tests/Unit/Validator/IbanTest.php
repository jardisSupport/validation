<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Iban;
use PHPUnit\Framework\TestCase;

final class IbanTest extends TestCase
{
    private Iban $validator;

    protected function setUp(): void
    {
        $this->validator = new Iban();
    }

    public function testValidIbans(): void
    {
        $validIbans = [
            'DE89370400440532013000',
            'GB82 WEST 1234 5698 7654 32',
            'FR1420041010050500013M02606',
            'IT60X0542811101000000123456',
            'ES9121000418450200051332',
            'NL91ABNA0417164300',
            'AT611904300234573201',
            'BE68539007547034',
        ];

        foreach ($validIbans as $iban) {
            $result = $this->validator->validateValue($iban);
            $this->assertNull($result, "Expected '{$iban}' to be valid");
        }
    }

    public function testInvalidIbans(): void
    {
        $invalidIbans = [
            'DE89370400440532013001',
            'XX82WEST12345698765432',
            'DE893704004405320130',
            'not-an-iban',
            '',
            '1234567890',
        ];

        foreach ($invalidIbans as $iban) {
            $result = $this->validator->validateValue($iban);
            $this->assertIsString($result, "Expected '{$iban}' to be invalid");
        }
    }

    public function testIbanWithSpaces(): void
    {
        // Spaces should be removed
        $result = $this->validator->validateValue('DE89 3704 0044 0532 0130 00');
        $this->assertNull($result);
    }

    public function testIbanWithoutSpaces(): void
    {
        $result = $this->validator->validateValue('DE89370400440532013000');
        $this->assertNull($result);
    }

    public function testCaseInsensitive(): void
    {
        $result = $this->validator->validateValue('de89370400440532013000');
        $this->assertNull($result);

        $result = $this->validator->validateValue('DE89370400440532013000');
        $this->assertNull($result);

        $result = $this->validator->validateValue('De89370400440532013000');
        $this->assertNull($result);
    }

    public function testInvalidCountryCode(): void
    {
        $result = $this->validator->validateValue('XX89370400440532013000');
        $this->assertIsString($result);
        $this->assertStringContainsString('country code', $result);
    }

    public function testInvalidLength(): void
    {
        // Too short for German IBAN (should be 22)
        $result = $this->validator->validateValue('DE8937040044053201300');
        $this->assertIsString($result);
        $this->assertStringContainsString('length', $result);

        // Too long for German IBAN
        $result = $this->validator->validateValue('DE893704004405320130000');
        $this->assertIsString($result);
        $this->assertStringContainsString('length', $result);
    }

    public function testInvalidChecksum(): void
    {
        // Valid format but invalid checksum (changed last digit)
        $result = $this->validator->validateValue('DE89370400440532013001');
        $this->assertIsString($result);
        $this->assertStringContainsString('checksum', $result);
    }

    public function testInvalidFormat(): void
    {
        $invalidFormats = [
            '8937040044053201300',
            'D89370400440532013000',
            'DEA9370400440532013000',
            'DE8A370400440532013000',
        ];

        foreach ($invalidFormats as $format) {
            $result = $this->validator->validateValue($format);
            $this->assertIsString($result, "Expected '{$format}' to be invalid");
        }
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
        $customMessage = 'Custom IBAN error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testVariousEuropeanCountries(): void
    {
        $europeanIbans = [
            'AT611904300234573201',
            'BE68539007547034',
            'CH9300762011623852957',
            'CZ6508000000192000145399',
            'DK5000400440116243',
            'EE382200221020145685',
            'FI2112345600000785',
            'GR1601101250000000012300695',
            'HR1210010051863000160',
            'IE29AIBK93115212345678',
            'LU280019400644750000',
            'NO9386011117947',
            'PL61109010140000071219812874',
            'PT50000201231234567890154',
            'SE4550000000058398257466',
            'SI56263300012039086',
            'SK3112000000198742637541',
        ];

        foreach ($europeanIbans as $iban) {
            $result = $this->validator->validateValue($iban);
            $this->assertNull($result, "Expected '{$iban}' to be valid");
        }
    }

    public function testSepaHelper(): void
    {
        $options = Iban::sepa();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('message', $options);
        $this->assertStringContainsString('SEPA', $options['message']);
        $this->assertTrue($options['sepa']);
    }

    public function testSepaValidation(): void
    {
        $options = Iban::sepa();

        // German IBAN (SEPA country) should pass
        $result = $this->validator->validateValue('DE89370400440532013000', $options);
        $this->assertNull($result);

        // GB IBAN (SEPA country) should pass
        $result = $this->validator->validateValue('GB82WEST12345698765432', $options);
        $this->assertNull($result);

        // Brazilian IBAN (non-SEPA country) should fail
        $result = $this->validator->validateValue('BR1800360305000010009795493C1', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('SEPA', $result);
    }

    public function testForCountryHelper(): void
    {
        $options = Iban::forCountry('DE');
        $this->assertIsArray($options);
        $this->assertArrayHasKey('message', $options);
        $this->assertStringContainsString('DE', $options['message']);
        $this->assertSame('DE', $options['country']);
    }

    public function testForCountryValidation(): void
    {
        $options = Iban::forCountry('DE');

        // German IBAN should pass
        $result = $this->validator->validateValue('DE89370400440532013000', $options);
        $this->assertNull($result);

        // GB IBAN should fail with country filter for DE
        $result = $this->validator->validateValue('GB82WEST12345698765432', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('DE', $result);
    }

    public function testForCountryCaseInsensitive(): void
    {
        // forCountry should accept lowercase and normalize
        $options = Iban::forCountry('de');
        $this->assertSame('DE', $options['country']);
    }

    public function testEmptyStringIsInvalid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
    }

    public function testCustomMessageIsRespectedForAllErrors(): void
    {
        $custom = 'Custom IBAN error for all cases';

        // Unknown country code
        $result = $this->validator->validateValue('XX89370400440532013000', ['message' => $custom]);
        $this->assertSame($custom, $result);

        // Invalid length (too short for DE)
        $result = $this->validator->validateValue('DE8937040044053201300', ['message' => $custom]);
        $this->assertSame($custom, $result);

        // Invalid checksum
        $result = $this->validator->validateValue('DE89370400440532013001', ['message' => $custom]);
        $this->assertSame($custom, $result);

        // Invalid basic format
        $result = $this->validator->validateValue('invalid', ['message' => $custom]);
        $this->assertSame($custom, $result);
    }

    public function testAlphanumericRequirement(): void
    {
        // IBAN must be alphanumeric after country code and check digits
        $result = $this->validator->validateValue('DE89@70400440532013000');
        $this->assertIsString($result);
    }
}
