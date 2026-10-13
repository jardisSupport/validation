<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use DateTimeImmutable;
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

        $result = $this->validator->validateValue(true);
        $this->assertIsString($result);
    }

    public function testDateTimeObjectIsValid(): void
    {
        $result = $this->validator->validateValue(new \DateTime('2024-06-15'));
        $this->assertNull($result);
    }

    public function testDateTimeImmutableObjectIsValid(): void
    {
        $result = $this->validator->validateValue(new DateTimeImmutable('2024-06-15'));
        $this->assertNull($result);
    }

    public function testDateTimeObjectWithMinMax(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01', 'max' => '2024-12-31'];

        // Within range
        $result = $this->validator->validateValue(new \DateTime('2024-06-15'), $options);
        $this->assertNull($result);

        // Before min
        $result = $this->validator->validateValue(new \DateTime('2023-12-31'), $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('after', $result);

        // After max
        $result = $this->validator->validateValue(new \DateTime('2025-01-01'), $options);
        $this->assertIsString($result);
        $this->assertStringContainsString('before', $result);
    }

    public function testDateTimeObjectBoundaryDates(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01', 'max' => '2024-12-31'];

        // Exact min
        $result = $this->validator->validateValue(new \DateTime('2024-01-01'), $options);
        $this->assertNull($result);

        // Exact max
        $result = $this->validator->validateValue(new \DateTime('2024-12-31'), $options);
        $this->assertNull($result);
    }

    public function testDateTimeImmutableObjectWithMinMax(): void
    {
        $options = ['format' => 'Y-m-d', 'min' => '2024-01-01', 'max' => '2024-12-31'];

        $result = $this->validator->validateValue(new DateTimeImmutable('2024-06-15'), $options);
        $this->assertNull($result);

        $result = $this->validator->validateValue(new DateTimeImmutable('2023-06-15'), $options);
        $this->assertIsString($result);
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

    public function testCustomMessageIsRespectedForAllErrors(): void
    {
        $custom = 'Custom date error for all cases';

        // Invalid format
        $result = $this->validator->validateValue('invalid', ['message' => $custom]);
        $this->assertSame($custom, $result);

        // Before min (string value)
        $result = $this->validator->validateValue(
            '2023-12-31',
            ['message' => $custom, 'format' => 'Y-m-d', 'min' => '2024-01-01']
        );
        $this->assertSame($custom, $result);

        // After max (string value)
        $result = $this->validator->validateValue(
            '2025-01-01',
            ['message' => $custom, 'format' => 'Y-m-d', 'max' => '2024-12-31']
        );
        $this->assertSame($custom, $result);

        // Before min (DateTime object)
        $result = $this->validator->validateValue(
            new \DateTime('2023-12-31'),
            ['message' => $custom, 'format' => 'Y-m-d', 'min' => '2024-01-01']
        );
        $this->assertSame($custom, $result);

        // After max (DateTime object)
        $result = $this->validator->validateValue(
            new \DateTime('2025-01-01'),
            ['message' => $custom, 'format' => 'Y-m-d', 'max' => '2024-12-31']
        );
        $this->assertSame($custom, $result);
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

    public function testRfc3339AcceptsWireFormats(): void
    {
        $valid = [
            '2026-10-12T10:00:00Z',
            '2026-10-12T10:00:00z',
            '2026-10-12t10:00:00Z',
            '2026-10-12T10:00:00.123Z',
            '2026-10-12T10:00:00+02:00',
            '2026-10-12T10:00:00.123456-05:30',
            '2024-02-29T23:59:59Z',
        ];

        foreach ($valid as $date) {
            $this->assertNull($this->validator->validateValue($date, DateTime::rfc3339()), "Expected '{$date}' valid");
        }
    }

    public function testRfc3339RejectsInvalidForms(): void
    {
        $invalid = [
            '2026-02-30T10:00:00Z',
            '2025-02-29T10:00:00Z',
            '2026-13-01T10:00:00Z',
            '2026-10-12T24:00:00Z',
            '2026-10-12T10:60:00Z',
            '2026-10-12T10:00:60Z',
            '2026-10-12T10:00:00+24:00',
            '2026-10-12T10:00:00+02:60',
            '2026-10-12T10:00:00',
            '2026-10-12T10:00:00.Z',
            '2026-10-12T10:00:00+0200',
            '2026-10-12 10:00:00',
            '2026-10-12 10:00:00Z',
            '2026-10-12',
            '12.10.2026',
            ' 2026-10-12T10:00:00Z',
            "2026-10-12T10:00:00Z\n",
            '',
        ];

        foreach ($invalid as $date) {
            $this->assertIsString(
                $this->validator->validateValue($date, DateTime::rfc3339()),
                'Expected ' . var_export($date, true) . ' invalid'
            );
        }
    }

    public function testRfc3339RejectsNonStringsAndAllowsNull(): void
    {
        $this->assertIsString($this->validator->validateValue(123, DateTime::rfc3339()));
        $this->assertIsString($this->validator->validateValue(['x'], DateTime::rfc3339()));
        $this->assertNull($this->validator->validateValue(null, DateTime::rfc3339()));
    }

    public function testRfc3339CustomMessageAndRange(): void
    {
        $options = DateTime::rfc3339() + ['message' => 'bad ts'];
        $this->assertSame('bad ts', $this->validator->validateValue('nope', $options));

        $range = DateTime::rfc3339() + ['min' => '2026-01-01T00:00:00Z', 'max' => '2026-12-31T23:59:59Z'];
        $this->assertNull($this->validator->validateValue('2026-06-01T12:00:00+02:00', $range));
        $this->assertStringContainsString('after', (string) $this->validator->validateValue('2025-12-31T23:59:59Z', $range));
        $this->assertStringContainsString('before', (string) $this->validator->validateValue('2027-01-01T00:00:00Z', $range));
    }

    public function testRfc3339AcceptsDateTimeObjects(): void
    {
        $this->assertNull($this->validator->validateValue(new DateTimeImmutable('2026-10-12'), DateTime::rfc3339()));
    }

    public function testDateOnlyIsCalendarStrict(): void
    {
        $this->assertNull($this->validator->validateValue('2026-10-12', DateTime::dateOnly()));
        $this->assertNull($this->validator->validateValue('2024-02-29', DateTime::dateOnly()));
        $this->assertIsString($this->validator->validateValue('2026-02-30', DateTime::dateOnly()));
        $this->assertIsString($this->validator->validateValue('2025-02-29', DateTime::dateOnly()));
        $this->assertIsString($this->validator->validateValue('2026-04-31', DateTime::dateOnly()));
    }

    public function testIso8601BehaviourUnchanged(): void
    {
        $this->assertNull($this->validator->validateValue('2026-10-12T10:00:00+02:00', DateTime::iso8601()));
        $this->assertIsString($this->validator->validateValue('2026-10-12T10:00:00Z', DateTime::iso8601()));
        $this->assertIsString($this->validator->validateValue('2026-10-12T10:00:00.123+02:00', DateTime::iso8601()));
    }
}
