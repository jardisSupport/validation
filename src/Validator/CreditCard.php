<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Validator;

use JardisPort\Validation\ValueValidatorInterface;

/**
 * Validates credit card numbers using the Luhn algorithm.
 * Optionally validates card type (Visa, Mastercard, Amex, etc.).
 */
final class CreditCard implements ValueValidatorInterface
{
    private const CARD_PATTERNS = [
        'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'mastercard' => '/^(?:5[1-5][0-9]{14}|2(?:22[1-9]|2[3-9][0-9]|[3-6][0-9]{2}|7[0-1][0-9]|720)[0-9]{12})$/',
        'amex' => '/^3[47][0-9]{13}$/',
        'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
    ];

    /**
     * Static helper: Visa card validation.
     *
     * @return array<string, mixed>
     */
    public static function visa(): array
    {
        return ['cardType' => 'visa'];
    }

    /**
     * Static helper: Mastercard validation.
     *
     * @return array<string, mixed>
     */
    public static function mastercard(): array
    {
        return ['cardType' => 'mastercard'];
    }

    /**
     * Static helper: American Express validation.
     *
     * @return array<string, mixed>
     */
    public static function amex(): array
    {
        return ['cardType' => 'amex'];
    }

    /**
     * Static helper: Discover card validation.
     *
     * @return array<string, mixed>
     */
    public static function discover(): array
    {
        return ['cardType' => 'discover'];
    }

    public function validateValue(mixed $value, array $options = []): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Credit card number must be a string';
        }

        $message = $options['message'] ?? 'Invalid credit card number';
        $cardType = $options['cardType'] ?? null;

        // Remove spaces and dashes
        $cardNumber = str_replace([' ', '-'], '', $value);

        // Check if only digits
        if (!ctype_digit($cardNumber)) {
            return isset($options['message']) ? $message : 'Credit card number must contain only digits';
        }

        // Check length (credit cards are typically 12-19 digits)
        $length = strlen($cardNumber);
        if ($length < 12 || $length > 19) {
            return $message;
        }

        // Reject obviously invalid patterns (all same digit)
        if (preg_match('/^(\d)\1+$/', $cardNumber)) {
            return $message;
        }

        // Validate card type pattern if specified
        if ($cardType !== null) {
            $cardTypeLower = strtolower($cardType);
            if (!isset(self::CARD_PATTERNS[$cardTypeLower])) {
                return sprintf('Unknown card type: %s', $cardType);
            }

            if (!preg_match(self::CARD_PATTERNS[$cardTypeLower], $cardNumber)) {
                return sprintf('Invalid %s card number', ucfirst($cardTypeLower));
            }
        }

        // Validate using Luhn algorithm
        if (!$this->validateLuhn($cardNumber)) {
            return $message;
        }

        return null;
    }

    private function validateLuhn(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        $shouldDouble = false;

        // Start from the rightmost digit and work left
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        return $sum % 10 === 0;
    }
}
