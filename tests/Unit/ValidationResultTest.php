<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisPort\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testEmptyResultIsValid(): void
    {
        $result = new ValidationResult([]);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testResultWithErrorsIsInvalid(): void
    {
        $errors = [
            'email' => ['Invalid email address'],
            'age' => ['Must be at least 18'],
        ];

        $result = new ValidationResult($errors);

        $this->assertFalse($result->isValid());
        $this->assertSame($errors, $result->getErrors());
    }

    public function testGetFieldErrors(): void
    {
        $errors = [
            'email' => ['Invalid email address', 'Email already exists'],
            'age' => ['Must be at least 18'],
        ];

        $result = new ValidationResult($errors);

        $this->assertSame(['Invalid email address', 'Email already exists'], $result->getFieldErrors('email'));
        $this->assertSame(['Must be at least 18'], $result->getFieldErrors('age'));
    }

    public function testGetFieldErrorsForNonExistentField(): void
    {
        $result = new ValidationResult([
            'email' => ['Invalid email address'],
        ]);

        $this->assertEmpty($result->getFieldErrors('nonexistent'));
    }

    public function testHasFieldError(): void
    {
        $result = new ValidationResult([
            'email' => ['Invalid email address'],
        ]);

        $this->assertTrue($result->hasFieldError('email'));
        $this->assertFalse($result->hasFieldError('age'));
    }

    public function testGetAllFieldsWithErrors(): void
    {
        $errors = [
            'email' => ['Invalid email address'],
            'age' => ['Must be at least 18'],
            'username' => ['Already taken'],
        ];

        $result = new ValidationResult($errors);

        $this->assertSame(['email', 'age', 'username'], $result->getAllFieldsWithErrors());
    }

    public function testCountErrors(): void
    {
        $errors = [
            'email' => ['Invalid email address', 'Email required'],
            'age' => ['Must be at least 18'],
        ];

        $result = new ValidationResult($errors);

        $this->assertSame(2, $result->getErrorCount());
    }

    public function testGetFirstErrorForField(): void
    {
        $errors = [
            'email' => ['First error', 'Second error', 'Third error'],
        ];

        $result = new ValidationResult($errors);

        $this->assertSame('First error', $result->getFirstError('email'));
    }

    public function testGetFirstErrorForNonExistentField(): void
    {
        $result = new ValidationResult([]);

        $this->assertNull($result->getFirstError('nonexistent'));
    }
}
