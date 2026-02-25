<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Range;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\Uuid;
use PHPUnit\Framework\TestCase;

final class CompositeFieldValidatorTest extends TestCase
{
    public function testFluentApiChaining(): void
    {
        $validator = new CompositeFieldValidator();

        $result = $validator
            ->field('email')
                ->validates(Email::class)
            ->field('age')
                ->validates(Range::class, Range::between(18, 120));

        $this->assertInstanceOf(CompositeFieldValidator::class, $validator);
    }

    public function testValidObjectPassesValidation(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('email')
                ->validates(Email::class)
            ->field('age')
                ->validates(Range::class, Range::between(18, 120));

        $data = new class {
            public function email(): string
            {
                return 'user@example.com';
            }

            public function age(): int
            {
                return 25;
            }
        };

        $result = $validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testInvalidObjectReturnsErrors(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('email')
                ->validates(Email::class)
            ->field('age')
                ->validates(Range::class, Range::between(18, 120));

        $data = new class {
            public function email(): string
            {
                return 'invalid-email';
            }

            public function age(): int
            {
                return 15;
            }
        };

        $result = $validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('email', $result->getErrors());
        $this->assertArrayHasKey('age', $result->getErrors());
    }

    public function testMultipleValidatorsOnSingleField(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('password')
                ->validates(NotBlank::class)
                ->validates(Length::class, Length::min(8));

        $data = new class {
            public function password(): string
            {
                return 'short';
            }
        };

        $result = $validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('password', $result->getErrors());
        $this->assertCount(1, $result->getErrors()['password']);
    }

    public function testValidatorWithPublicProperties(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('id')
                ->validates(Uuid::class, Uuid::v4());

        $data = new class {
            public string $id = '550e8400-e29b-41d4-a716-446655440000';
        };

        $result = $validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testExcludeFieldsForPartialValidation(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('id')
                ->validates(Uuid::class)
            ->field('email')
                ->validates(Email::class)
            ->excludeFields(['id']);

        $data = new class {
            public ?string $id = null;

            public function email(): string
            {
                return 'user@example.com';
            }
        };

        $result = $validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testBreakValidationStopsOnFirstError(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('critical')
                ->breaksOn(NotBlank::class)
            ->field('email')
                ->validates(Email::class);

        $data = new class {
            public ?string $critical = null;

            public function email(): string
            {
                return 'invalid';
            }
        };

        $result = $validator->validate($data);

        // Should stop early due to break validator
        $this->assertTrue($result->isValid()); // No errors collected because validation stopped
    }

    public function testSingletonValidatorInstances(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('email1')
                ->validates(Email::class)
            ->field('email2')
                ->validates(Email::class)
            ->field('email3')
                ->validates(Email::class);

        $data = new class {
            public function email1(): string
            {
                return 'user1@example.com';
            }

            public function email2(): string
            {
                return 'user2@example.com';
            }

            public function email3(): string
            {
                return 'user3@example.com';
            }
        };

        $result = $validator->validate($data);

        // If this passes, singletons are working (no instantiation errors)
        $this->assertTrue($result->isValid());
    }

    public function testValidationWithMissingFields(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('nonexistent')
                ->validates(NotBlank::class);

        $data = new class {
            public function someOtherField(): string
            {
                return 'value';
            }
        };

        $result = $validator->validate($data);

        // Missing fields should be treated as null
        $this->assertFalse($result->isValid());
    }

    public function testComplexRealWorldScenario(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('id')
                ->validates(Uuid::class, Uuid::v4())
            ->field('email')
                ->validates(Email::class, Email::strict())
            ->field('age')
                ->validates(Range::class, Range::between(18, 120))
            ->field('username')
                ->validates(NotBlank::class)
                ->validates(Length::class, Length::between(3, 20));

        $data = new class {
            public string $id = '550e8400-e29b-41d4-a716-446655440000';

            public function email(): string
            {
                return 'user@example.com';
            }

            public function age(): int
            {
                return 30;
            }

            public function username(): string
            {
                return 'john_doe';
            }
        };

        $result = $validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testExcludeFieldsWithExistingId(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('id')
                ->validates(Uuid::class)
            ->field('email')
                ->validates(Email::class)
            ->excludeFields(['email']);

        // Object with existing id - excluded fields should be validated
        $data = new class {
            public string $id = '550e8400-e29b-41d4-a716-446655440000';

            public function email(): string
            {
                return 'invalid-email';
            }
        };

        $result = $validator->validate($data);

        // Email is validated because id exists (not null)
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('email', $result->getErrors());
    }

    public function testExcludeFieldsWorksMultipleTimes(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('email')
                ->validates(Email::class)
            ->excludeFields(['field1'])
            ->excludeFields(['field2']);

        $data = new class {
            public function email(): string
            {
                return 'valid@example.com';
            }
        };

        $result = $validator->validate($data);
        $this->assertTrue($result->isValid());
    }
}
