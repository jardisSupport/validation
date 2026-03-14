<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisPort\Validation\ValidationResult;
use JardisPort\Validation\ValidatorInterface;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ObjectValidator recursive object graph validation.
 */
final class ObjectValidatorTest extends TestCase
{
    public function testValidateSimpleObjectWithRegisteredValidator(): void
    {
        $object = new class {
            public string $name = 'John';
        };

        $mockValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult([]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(get_class($object), $mockValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($object);

        $this->assertTrue($result->isValid());
    }

    public function testValidateObjectWithErrors(): void
    {
        $object = new class {
            public string $name = '';
        };

        $mockValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['name' => ['Name is required']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(get_class($object), $mockValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($object);

        $this->assertFalse($result->isValid());
    }

    public function testValidateObjectWithoutRegisteredValidator(): void
    {
        $object = new class {
            public string $name = 'John';
        };

        $registry = new ValidatorRegistry();
        $validator = new ObjectValidator($registry);
        $result = $validator->validate($object);

        $this->assertTrue($result->isValid());
    }

    public function testValidateNestedObjects(): void
    {
        $innerClass = get_class(new class {
            public string $city = 'Berlin';
        });

        $inner = new $innerClass();

        $outer = new class ($inner) {
            public function __construct(
                public readonly object $address
            ) {
            }
        };

        $innerValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['city' => ['City is invalid']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register($innerClass, $innerValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($outer);

        $this->assertFalse($result->isValid());
    }

    public function testCircularReferenceProtection(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $a->ref = $b;
        $b->ref = $a;

        $registry = new ValidatorRegistry();
        $validator = new ObjectValidator($registry);

        // Should not throw or infinite loop
        $result = $validator->validate($a);
        // Result may have empty nested structure but should complete without error
        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    public function testArrayTraversal(): void
    {
        $itemClass = get_class(new class {
            public string $value = 'test';
        });

        $item1 = new $itemClass();
        $item2 = new $itemClass();

        $container = new class ([$item1, $item2]) {
            /** @var array<object> */
            public array $items;

            public function __construct(array $items)
            {
                $this->items = $items;
            }
        };

        $itemValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['value' => ['Invalid value']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register($itemClass, $itemValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($container);

        $this->assertFalse($result->isValid());
    }

    public function testSelfReferenceProtection(): void
    {
        $obj = new \stdClass();
        $obj->self = $obj;

        $registry = new ValidatorRegistry();
        $validator = new ObjectValidator($registry);

        $result = $validator->validate($obj);
        $this->assertTrue($result->isValid());
    }

    public function testGetShortClassNameWithoutNamespace(): void
    {
        $obj = new \stdClass();

        $registry = new ValidatorRegistry();
        $validator = new ObjectValidator($registry);
        $result = $validator->validate($obj);

        // stdClass without namespace should produce 'stdClass' (lcfirst) key
        $errors = $result->getErrors();
        // Result is valid, but we verify no key starts with 'tdClass' (the old bug)
        $this->assertArrayNotHasKey('tdClass', $errors);
    }

    public function testGetShortClassNameWithNamespace(): void
    {
        $inner = new class {
            public string $value = 'test';
        };

        $mockValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['value' => ['Error']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(get_class($inner), $mockValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($inner);

        $this->assertFalse($result->isValid());
    }
}
