<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Validator;

use JardisSupport\Contract\Validation\MissingValueValidatorInterface;
use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Validator\ChildPresence;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

final class ChildPresenceTest extends TestCase
{
    private ChildPresence $validator;

    protected function setUp(): void
    {
        $this->validator = new ChildPresence();
    }

    private function parent(?string $child): object
    {
        return new class ($child) {
            public function __construct(private ?string $child)
            {
            }
        };
    }

    public function testNullValueIsValid(): void
    {
        $this->assertNull($this->validator->validateValue(null, ChildPresence::requiredChild('child')));
    }

    public function testObjectWithChildSetIsValid(): void
    {
        $this->assertNull($this->validator->validateValue($this->parent('x'), ChildPresence::requiredChild('child')));
    }

    public function testObjectWithNullChildReturnsMessageNamingField(): void
    {
        $result = $this->validator->validateValue($this->parent(null), ChildPresence::requiredChild('child'));
        $this->assertSame('child can not be empty', $result);
    }

    public function testArrayWithOneMissingChildReturnsMessage(): void
    {
        $result = $this->validator->validateValue(
            [$this->parent('x'), $this->parent(null)],
            ChildPresence::requiredChild('child')
        );
        $this->assertSame('child can not be empty', $result);
    }

    public function testArrayWithAllChildrenSetIsValid(): void
    {
        $result = $this->validator->validateValue(
            [$this->parent('x'), $this->parent('y')],
            ChildPresence::requiredChild('child')
        );
        $this->assertNull($result);
    }

    public function testNonObjectInArrayReturnsMessage(): void
    {
        $result = $this->validator->validateValue(['scalar'], ChildPresence::requiredChild('child'));
        $this->assertSame('child can not be empty', $result);
    }

    public function testMissingPropertyReturnsMessage(): void
    {
        $result = $this->validator->validateValue($this->parent('x'), ChildPresence::requiredChild('other'));
        $this->assertSame('other can not be empty', $result);
    }

    public function testCustomMessage(): void
    {
        $result = $this->validator->validateValue(
            $this->parent(null),
            ChildPresence::requiredChild('child', 'Child is required')
        );
        $this->assertSame('Child is required', $result);
    }

    public function testWithoutChildFieldOptionNothingIsChecked(): void
    {
        $this->assertNull($this->validator->validateValue($this->parent(null)));
    }

    public function testIsMissingValueValidator(): void
    {
        $this->assertInstanceOf(MissingValueValidatorInterface::class, $this->validator);
    }

    public function testViaCompositeFieldValidatorReportsKindMissing(): void
    {
        $validator = new CompositeFieldValidator();
        $validator->field('parent')->validates(ChildPresence::class, ChildPresence::requiredChild('child'));
        $data = new class ($this->parent(null)) {
            public function __construct(public object $parent)
            {
            }
        };
        $registry = (new ValidatorRegistry())->register($data::class, $validator);

        $result = (new ObjectValidator($registry))->validate($data);

        $this->assertFalse($result->isValid());
        $key = array_key_first($result->getKinds());
        $this->assertSame(ValidationResult::KIND_MISSING, $result->getKinds()[$key]['parent'][0]);
    }
}
