<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Validation\Validator\Callback;
use PHPUnit\Framework\TestCase;

final class CallbackTest extends TestCase
{
    public function testValidCallback(): void
    {
        $validator = new Callback(function ($value) {
            return $value === 'valid' ? null : 'Invalid value';
        });

        $result = $validator->validateValue('valid');
        $this->assertNull($result);
    }

    public function testInvalidCallback(): void
    {
        $validator = new Callback(function ($value) {
            return $value === 'valid' ? null : 'Invalid value';
        });

        $result = $validator->validateValue('invalid');
        $this->assertIsString($result);
        $this->assertSame('Invalid value', $result);
    }

    public function testCallbackWithNullValue(): void
    {
        $validator = new Callback(function ($value) {
            return $value === null ? null : 'Must be null';
        });

        $result = $validator->validateValue(null);
        $this->assertNull($result);

        $result = $validator->validateValue('not-null');
        $this->assertIsString($result);
    }

    public function testCallbackWithNumericValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_numeric($value)) {
                return 'Value must be numeric';
            }
            if ($value < 0 || $value > 100) {
                return 'Value must be between 0 and 100';
            }
            return null;
        });

        $result = $validator->validateValue(50);
        $this->assertNull($result);

        $result = $validator->validateValue(150);
        $this->assertSame('Value must be between 0 and 100', $result);

        $result = $validator->validateValue('not-a-number');
        $this->assertSame('Value must be numeric', $result);
    }

    public function testCallbackWithArrayValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_array($value)) {
                return 'Value must be an array';
            }
            if (count($value) < 2) {
                return 'Array must have at least 2 elements';
            }
            return null;
        });

        $result = $validator->validateValue([1, 2, 3]);
        $this->assertNull($result);

        $result = $validator->validateValue([1]);
        $this->assertSame('Array must have at least 2 elements', $result);

        $result = $validator->validateValue('not-an-array');
        $this->assertSame('Value must be an array', $result);
    }

    public function testCallbackWithStringLengthValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_string($value)) {
                return 'Value must be a string';
            }
            if (strlen($value) < 5) {
                return 'String must be at least 5 characters';
            }
            return null;
        });

        $result = $validator->validateValue('hello world');
        $this->assertNull($result);

        $result = $validator->validateValue('hi');
        $this->assertSame('String must be at least 5 characters', $result);

        $result = $validator->validateValue(123);
        $this->assertSame('Value must be a string', $result);
    }

    public function testCallbackWithEmailValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Invalid email address';
            }
            return null;
        });

        $result = $validator->validateValue('test@example.com');
        $this->assertNull($result);

        $result = $validator->validateValue('invalid-email');
        $this->assertSame('Invalid email address', $result);
    }

    public function testCallbackWithComplexLogic(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_array($value)) {
                return 'Value must be an array';
            }
            if (!isset($value['username']) || !isset($value['password'])) {
                return 'Username and password are required';
            }
            if (strlen($value['username']) < 3) {
                return 'Username must be at least 3 characters';
            }
            if (strlen($value['password']) < 8) {
                return 'Password must be at least 8 characters';
            }
            return null;
        });

        $result = $validator->validateValue(['username' => 'john', 'password' => 'secret123']);
        $this->assertNull($result);

        $result = $validator->validateValue(['username' => 'jo', 'password' => 'secret123']);
        $this->assertSame('Username must be at least 3 characters', $result);

        $result = $validator->validateValue(['username' => 'john', 'password' => 'short']);
        $this->assertSame('Password must be at least 8 characters', $result);

        $result = $validator->validateValue(['username' => 'john']);
        $this->assertSame('Username and password are required', $result);
    }

    public function testCallbackWithBooleanReturn(): void
    {
        $validator = new Callback(function ($value) {
            return $value === 'test' ? null : 'Not test';
        });

        $result = $validator->validateValue('test');
        $this->assertNull($result);

        $result = $validator->validateValue('other');
        $this->assertSame('Not test', $result);
    }

    public function testCallbackWithTypeChecking(): void
    {
        $validator = new Callback(function ($value) {
            if (is_int($value)) {
                return null;
            }
            return 'Value must be an integer';
        });

        $result = $validator->validateValue(42);
        $this->assertNull($result);

        $result = $validator->validateValue('42');
        $this->assertSame('Value must be an integer', $result);

        $result = $validator->validateValue(42.5);
        $this->assertSame('Value must be an integer', $result);
    }

    public function testCallbackWithDynamicErrorMessages(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_numeric($value)) {
                return 'Value must be numeric';
            }
            if ($value < 0) {
                return "Value {$value} is negative";
            }
            if ($value > 100) {
                return "Value {$value} exceeds maximum of 100";
            }
            return null;
        });

        $result = $validator->validateValue(-5);
        $this->assertSame('Value -5 is negative', $result);

        $result = $validator->validateValue(150);
        $this->assertSame('Value 150 exceeds maximum of 100', $result);
    }

    public function testCallbackWithNestedValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!is_array($value)) {
                return 'Value must be an array';
            }
            foreach ($value as $item) {
                if (!is_numeric($item)) {
                    return 'All items must be numeric';
                }
                if ($item < 0) {
                    return 'All items must be positive';
                }
            }
            return null;
        });

        $result = $validator->validateValue([1, 2, 3]);
        $this->assertNull($result);

        $result = $validator->validateValue([1, -2, 3]);
        $this->assertSame('All items must be positive', $result);

        $result = $validator->validateValue([1, 'two', 3]);
        $this->assertSame('All items must be numeric', $result);
    }

    public function testCallbackWithObjectValidation(): void
    {
        $validator = new Callback(function ($value) {
            if (!$value instanceof \stdClass) {
                return 'Value must be an stdClass instance';
            }
            if (!isset($value->name)) {
                return 'Object must have a name property';
            }
            return null;
        });

        $obj = new \stdClass();
        $obj->name = 'Test';
        $result = $validator->validateValue($obj);
        $this->assertNull($result);

        $obj2 = new \stdClass();
        $result = $validator->validateValue($obj2);
        $this->assertSame('Object must have a name property', $result);

        $result = $validator->validateValue('not-an-object');
        $this->assertSame('Value must be an stdClass instance', $result);
    }

    public function testCallbackWithEmptyValues(): void
    {
        $validator = new Callback(function ($value) {
            if (empty($value) && $value !== 0 && $value !== '0') {
                return 'Value cannot be empty';
            }
            return null;
        });

        $result = $validator->validateValue(0);
        $this->assertNull($result);

        $result = $validator->validateValue('0');
        $this->assertNull($result);

        $result = $validator->validateValue('');
        $this->assertSame('Value cannot be empty', $result);

        $result = $validator->validateValue(null);
        $this->assertSame('Value cannot be empty', $result);
    }

    public function testCallbackReturningNull(): void
    {
        $validator = new Callback(function ($value) {
            return null;
        });

        $result = $validator->validateValue('anything');
        $this->assertNull($result);

        $result = $validator->validateValue(null);
        $this->assertNull($result);

        $result = $validator->validateValue([]);
        $this->assertNull($result);
    }

    public function testCallbackAlwaysReturningError(): void
    {
        $validator = new Callback(function ($value) {
            return 'Always invalid';
        });

        $result = $validator->validateValue('test');
        $this->assertSame('Always invalid', $result);

        $result = $validator->validateValue(123);
        $this->assertSame('Always invalid', $result);
    }

    public function testCallbackWithClosureCapturing(): void
    {
        $minLength = 5;
        $validator = new Callback(function ($value) use ($minLength) {
            if (!is_string($value)) {
                return 'Value must be a string';
            }
            if (strlen($value) < $minLength) {
                return "String must be at least {$minLength} characters";
            }
            return null;
        });

        $result = $validator->validateValue('hello world');
        $this->assertNull($result);

        $result = $validator->validateValue('hi');
        $this->assertSame('String must be at least 5 characters', $result);
    }
}
