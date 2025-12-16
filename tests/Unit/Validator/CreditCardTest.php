<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\CreditCard;
use PHPUnit\Framework\TestCase;

final class CreditCardTest extends TestCase
{
    private CreditCard $validator;

    protected function setUp(): void
    {
        $this->validator = new CreditCard();
    }

    public function testValidCreditCards(): void
    {
        $validCards = [
            '4532015112830366',
            '4716-2610-5188-5668', // Valid Luhn checksum
            '4485275742308327', // Valid Luhn checksum
            '5425233430109903',
            '2221000000000009',
            '378282246310005',
            '371449635398431',
            '6011111111111117',
            '6011000990139424',
        ];

        foreach ($validCards as $card) {
            $result = $this->validator->validateValue($card);
            $this->assertNull($result, "Expected '{$card}' to be valid");
        }
    }

    public function testInvalidCreditCards(): void
    {
        $invalidCards = [
            '4532015112830367',
            '1234567812345678',
            'not-a-card',
            '',
            '123',
            '0000000000000000',
        ];

        foreach ($invalidCards as $card) {
            $result = $this->validator->validateValue($card);
            $this->assertIsString($result, "Expected '{$card}' to be invalid");
        }
    }

    public function testCardNumberWithSpaces(): void
    {
        $result = $this->validator->validateValue('4532 0151 1283 0366');
        $this->assertNull($result);
    }

    public function testCardNumberWithDashes(): void
    {
        $result = $this->validator->validateValue('4532-0151-1283-0366');
        $this->assertNull($result);
    }

    public function testValidVisaCards(): void
    {
        $visaCards = [
            '4532015112830366',
            '4716261051885668', // Valid Luhn checksum
            '4485275742308327', // Valid Luhn checksum
        ];

        foreach ($visaCards as $card) {
            $result = $this->validator->validateValue($card, ['cardType' => 'visa']);
            $this->assertNull($result, "Expected '{$card}' to be valid Visa");
        }
    }

    public function testInvalidVisaCards(): void
    {
        // Mastercard number
        $result = $this->validator->validateValue('5425233430109903', ['cardType' => 'visa']);
        $this->assertIsString($result);
        $this->assertStringContainsString('Visa', $result);
    }

    public function testValidMastercardCards(): void
    {
        $mastercardCards = [
            '5425233430109903',
            '5555555555554444',
            '5105105105105100',
        ];

        foreach ($mastercardCards as $card) {
            $result = $this->validator->validateValue($card, ['cardType' => 'mastercard']);
            $this->assertNull($result, "Expected '{$card}' to be valid Mastercard");
        }
    }

    public function testInvalidMastercardCards(): void
    {
        // Visa number
        $result = $this->validator->validateValue('4532015112830366', ['cardType' => 'mastercard']);
        $this->assertIsString($result);
        $this->assertStringContainsString('Mastercard', $result);
    }

    public function testValidAmexCards(): void
    {
        $amexCards = [
            '378282246310005',
            '371449635398431',
            '378734493671000',
        ];

        foreach ($amexCards as $card) {
            $result = $this->validator->validateValue($card, ['cardType' => 'amex']);
            $this->assertNull($result, "Expected '{$card}' to be valid Amex");
        }
    }

    public function testInvalidAmexCards(): void
    {
        // Visa number
        $result = $this->validator->validateValue('4532015112830366', ['cardType' => 'amex']);
        $this->assertIsString($result);
        $this->assertStringContainsString('Amex', $result);
    }

    public function testValidDiscoverCards(): void
    {
        $discoverCards = [
            '6011111111111117',
            '6011000990139424',
            '6011981111111113',
        ];

        foreach ($discoverCards as $card) {
            $result = $this->validator->validateValue($card, ['cardType' => 'discover']);
            $this->assertNull($result, "Expected '{$card}' to be valid Discover");
        }
    }

    public function testLuhnAlgorithmValidation(): void
    {
        // Valid Luhn
        $result = $this->validator->validateValue('4532015112830366');
        $this->assertNull($result);

        // Invalid Luhn (changed last digit)
        $result = $this->validator->validateValue('4532015112830367');
        $this->assertIsString($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(4532015112830366);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom card error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testNonDigitCharacters(): void
    {
        $result = $this->validator->validateValue('4532-0151-1283-036X');
        $this->assertIsString($result);
        $this->assertStringContainsString('digits', $result);

        $result = $this->validator->validateValue('4532 0151 1283 036a');
        $this->assertIsString($result);
        $this->assertStringContainsString('digits', $result);
    }

    public function testUnknownCardType(): void
    {
        $result = $this->validator->validateValue('4532015112830366', ['cardType' => 'unknown']);
        $this->assertIsString($result);
        $this->assertStringContainsString('Unknown card type', $result);
    }

    public function testVisaHelper(): void
    {
        $options = CreditCard::visa();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('cardType', $options);
        $this->assertSame('visa', $options['cardType']);
    }

    public function testMastercardHelper(): void
    {
        $options = CreditCard::mastercard();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('cardType', $options);
        $this->assertSame('mastercard', $options['cardType']);
    }

    public function testAmexHelper(): void
    {
        $options = CreditCard::amex();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('cardType', $options);
        $this->assertSame('amex', $options['cardType']);
    }

    public function testDiscoverHelper(): void
    {
        $options = CreditCard::discover();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('cardType', $options);
        $this->assertSame('discover', $options['cardType']);
    }

    public function testEmptyStringIsInvalid(): void
    {
        $result = $this->validator->validateValue('');
        $this->assertIsString($result);
    }

    public function testVeryShortNumber(): void
    {
        $result = $this->validator->validateValue('123');
        $this->assertIsString($result);
    }

    public function testVeryLongNumber(): void
    {
        $result = $this->validator->validateValue('12345678901234567890');
        $this->assertIsString($result);
    }
}
