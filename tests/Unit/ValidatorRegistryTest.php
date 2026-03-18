<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisPort\Validation\ValidationResult;
use JardisPort\Validation\ValidatorInterface;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ValidatorRegistry class matching and retrieval.
 */
final class ValidatorRegistryTest extends TestCase
{
    private ValidatorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ValidatorRegistry();
    }

    public function testRegisterAndGetExactMatch(): void
    {
        $validator = $this->createMockValidator();
        $object = new \stdClass();

        $this->registry->register(\stdClass::class, $validator);

        $result = $this->registry->getValidator($object);
        $this->assertSame($validator, $result);
    }

    public function testGetValidatorReturnsNullForUnregistered(): void
    {
        $object = new \stdClass();
        $result = $this->registry->getValidator($object);
        $this->assertNull($result);
    }

    public function testParentClassMatching(): void
    {
        $parentClass = new class {
        };

        $childClass = new class extends \stdClass {
        };

        $validator = $this->createMockValidator();
        $this->registry->register(\stdClass::class, $validator);

        $result = $this->registry->getValidator($childClass);
        $this->assertSame($validator, $result);
    }

    public function testInterfaceMatching(): void
    {
        $validator = $this->createMockValidator();
        $this->registry->register(\Countable::class, $validator);

        $object = new class implements \Countable {
            public function count(): int
            {
                return 0;
            }
        };

        $result = $this->registry->getValidator($object);
        $this->assertSame($validator, $result);
    }

    public function testExactMatchTakesPriority(): void
    {
        $parentValidator = $this->createMockValidator();
        $childValidator = $this->createMockValidator();

        $childClass = get_class(new class extends \stdClass {
        });

        $this->registry->register(\stdClass::class, $parentValidator);
        $this->registry->register($childClass, $childValidator);

        $child = new $childClass();
        $result = $this->registry->getValidator($child);
        $this->assertSame($childValidator, $result);
    }

    public function testRegisterReturnsSelf(): void
    {
        $validator = $this->createMockValidator();
        $result = $this->registry->register(\stdClass::class, $validator);
        $this->assertSame($this->registry, $result);
    }

    public function testFluentRegistration(): void
    {
        $validator1 = $this->createMockValidator();
        $validator2 = $this->createMockValidator();

        $this->registry
            ->register(\stdClass::class, $validator1)
            ->register(\Countable::class, $validator2);

        $this->assertSame($validator1, $this->registry->getValidator(new \stdClass()));
    }

    private function createMockValidator(): ValidatorInterface
    {
        return new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult([]);
            }
        };
    }
}
