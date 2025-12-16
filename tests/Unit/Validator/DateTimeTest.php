<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\DateTime;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    private DateTime $validator;

    protected function setUp(): void
    {
        $this->validator = new DateTime();
    }

    public function testValidIso8601Dates(): void
    {
        $validDates = [
            '2024-01-15T10:30:00+00:00',
            '2024-12-31T23:59:59+00:00',
            '2024-06-15T14:30:00+02:00',
            '2024-03-10T08:15:00-05:00',
        ];

        foreach ($validDates as $date) {
            $result = $this->validator->validateValue($date);
            $this->assertNull($result, "Expected '{$date}' to be valid");
        }
    }

    public function testInvalidIso8601Dates(): void
    {
        $invalidDates = [
            'not-a-date',
            '2024-13-01T10:30:00+00:00',
            '2024-01-32T10:30:00+00:00',
            '2024-01-15T25:30:00+00:00',
            '2024-01-15 10:30:00',
            '',
        ];

        foreach ($invalidDates as $date) {
            $result = $this->validator->validateValue($date);
            $this->assertIsString($result, "Expected '{$date}' to be invalid");
        }
    }

    public function testDateOnlyFormat(): void
    {
        $result = $this->validator->validateValue('2024-01-15', ['format' => 'Y-m-d']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2024-13-01', ['format' => 'Y-m-d']);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('15-01-2024', ['format' => 'Y-m-d']);
        $this->assertIsString($result);
    }

    public function testCustomDateFormat(): void
    {
        // US format: MM/DD/YYYY
        $result = $this->validator->validateValue('01/15/2024', ['format' => 'm/d/Y']);
        $this->assertNull($result);

        // European format: DD.MM.YYYY
        $result = $this->validator->validateValue('15.01.2024', ['format' => 'd.m.Y']);
        $this->assertNull($result);

        // Wrong format
        $result = $this->validator->validateValue('2024-01-15', ['format' => 'm/d/Y']);
        $this->assertIsString($result);
    }

    public function testMinimumDate(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01'];

        $result = $this->validator->validateValue('2024-06-15', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2023-12-31', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('after', $result);
    }

    public function testMaximumDate(): void
    {
        $options = ['format' => 'Y-m-d', 'max' => '2024-12-31'];

        $result = $this->validator->validateValue('2024-06-15', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2025-01-01', $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('before', $result);
    }

    public function testDateRange(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01', 'max' => '2024-12-31'];

        $result = $this->validator->validateValue('2024-06-15', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2023-12-31', $options);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('2025-01-01', $options);
        $this->assertIsString($result);
    }

    public function testBoundaryDates(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01', 'max' => '2024-12-31'];

        $result = $this->validator->validateValue('2024-01-01', $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2024-12-31', $options);
        $this->assertNull($result);
    }

    public function testNullValueIsAllowed(): void
    {
        $result = $this->validator->validateValue(null);
        $this->assertNull($result);
    }

    public function testNonStringValueReturnsError(): void
    {
        $result = $this->validator->validateValue(20240115);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);

        $result = $this->validator->validateValue(['array']);
        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom date error';
        $result = $this->validator->validateValue('invalid', ['message' => $customMessage]);
        $this->assertSame($customMessage, $result);
    }

    public function testIso8601Helper(): void
    {
        $options = DateTime::iso8601();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('format', $options);
        $this->assertSame('Y-m-d\TH:i:sP', $options['format']);
    }

    public function testBetweenHelper(): void
    {
        $options = DateTime::between('2024-01-01', '2024-12-31', 'Y-m-d');
        $this->assertIsArray($options);
        $this->assertSame('Y-m-d', $options['format']);
        $this->assertSame('2024-01-01', $options['min']);
        $this->assertSame('2024-12-31', $options['max']);
    }

    public function testDateOnlyHelper(): void
    {
        $options = DateTime::dateOnly();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('format', $options);
        $this->assertSame('Y-m-d', $options['format']);
    }

    public function testLeapYear(): void
    {
        $result = $this->validator->validateValue('2024-02-29', ['format' => 'Y-m-d']);
        $this->assertNull($result);

        $result = $this->validator->validateValue('2023-02-29', ['format' => 'Y-m-d']);
        $this->assertIsString($result);
    }

    public function testTimeFormats(): void
    {
        // 24-hour format
        $result = $this->validator->validateValue('14:30:00', ['format' => 'H:i:s']);
        $this->assertNull($result);

        // 12-hour format
        $result = $this->validator->validateValue('02:30:00 PM', ['format' => 'h:i:s A']);
        $this->assertNull($result);
    }

    public function testStrictFormatValidation(): void
    {
        // DateTime validator checks that the formatted output matches input
        $result = $this->validator->validateValue('2024-1-5', ['format' => 'Y-m-d']);
        $this->assertIsString($result);

        $result = $this->validator->validateValue('2024-01-05', ['format' => 'Y-m-d']);
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

    public function testPartialDates(): void
    {
        // Year and month only
        $result = $this->validator->validateValue('2024-01', ['format' => 'Y-m']);
        $this->assertNull($result);

        // Year only
        $result = $this->validator->validateValue('2024', ['format' => 'Y']);
        $this->assertNull($result);
    }
}
