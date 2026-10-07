<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Validation\MissingValueValidatorInterface;
use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Branch;
use JardisSupport\Validation\Tests\Support\Link;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\NotEmpty;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The kinds tree (missing|invalid) mirrors the error tree leaf for leaf.
 */
final class ValidationKindsTest extends TestCase
{
    public function testInstalledContractsCarryKinds(): void
    {
        $this->assertTrue(method_exists(ValidationResult::class, 'getKinds'));
        $this->assertTrue(interface_exists(MissingValueValidatorInterface::class));
    }

    public function testOnlyNotBlankIsMissingValueValidator(): void
    {
        $this->assertInstanceOf(MissingValueValidatorInterface::class, new NotBlank());
        $this->assertNotInstanceOf(MissingValueValidatorInterface::class, new NotEmpty());
    }

    public function testFlatObjectKindsMirrorErrors(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('name')->validates(NotBlank::class)->validates(NotEmpty::class)
            ->field('email')->validates(Email::class);
        $data = new class {
            public ?string $name = null;
            public string $email = 'nope';
        };
        $registry = (new ValidatorRegistry())->register($data::class, $validator);

        $result = (new ObjectValidator($registry))->validate($data);

        $this->assertSame($this->shape($result->getErrors()), $this->shape($result->getKinds()));
        $key = array_key_first($result->getErrors());
        $this->assertSame(
            ['missing', 'invalid'],
            [$result->getKinds()[$key]['name'][0], $result->getKinds()[$key]['name'][1]]
        );
        $this->assertSame('invalid', $result->getKinds()[$key]['email'][0]);
    }

    public function testNestedObjectsWithIntKeysAndLists(): void
    {
        $holder = new class {
            /** @var array<int, object> */
            public array $items = [];
        };
        $item = static fn (): object => new class {
            public ?string $sku = null;
        };
        $holder->items = [$item(), [$item(), $item()]];

        $itemValidator = new CompositeFieldValidator();
        $itemValidator->field('sku')->validates(NotBlank::class);
        $registry = (new ValidatorRegistry())->register(get_class($item()), $itemValidator);
        $holderValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['x' => ['e']], ['x' => ['invalid']]);
            }
        };
        $registry->register($holder::class, $holderValidator);

        $result = (new ObjectValidator($registry))->validate($holder);

        $this->assertNotEmpty($result->getErrors());
        $this->assertSame($this->shape($result->getErrors()), $this->shape($result->getKinds()));
    }

    public function testKeyCollisionKeepsStructuresEqual(): void
    {
        $branchValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['link' => ['clash']], ['link' => ['missing']]);
            }
        };
        $linkValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['item' => ['bad']], ['item' => ['invalid']]);
            }
        };
        $registry = (new ValidatorRegistry())
            ->register(Branch::class, $branchValidator)
            ->register(Link::class, $linkValidator);

        $result = (new ObjectValidator($registry))->validate(new Branch(new Link('')));

        $this->assertSame($this->shape($result->getErrors()), $this->shape($result->getKinds()));
        $this->assertSame('missing', $result->getKinds()['branch']['link'][0]);
    }

    public function testNotBlankWithCustomMessageIsMissing(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('name')->validates(NotBlank::class, NotBlank::required('Pflichtfeld'));
        $result = $validator->validate(new class {
            public ?string $name = null;
        });

        $this->assertSame(['Pflichtfeld'], $result->getErrors()['name']);
        $this->assertSame(['missing'], $result->getKinds()['name']);
    }

    public function testEmailWithEmptyLookingMessageIsInvalid(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('email')->validates(Email::class, ['message' => 'Field can not be empty']);
        $result = $validator->validate(new class {
            public string $email = 'nope';
        });

        $this->assertSame(['Field can not be empty'], $result->getErrors()['email']);
        $this->assertSame(['invalid'], $result->getKinds()['email']);
    }

    public function testBreakValidatorYieldsEmptyTrees(): void
    {
        $validator = new CompositeFieldValidator();
        $validator
            ->field('id')->breaksOn(NotBlank::class)
            ->field('name')->validates(NotBlank::class);
        $result = $validator->validate(new class {
            public ?string $id = null;
            public ?string $name = null;
        });

        $this->assertSame([], $result->getErrors());
        $this->assertSame([], $result->getKinds());
    }

    /**
     * @param array<int|string, mixed> $tree
     * @return array<int|string, mixed>
     */
    private function shape(array $tree): array
    {
        array_walk_recursive($tree, static function (mixed &$leaf): void {
            $leaf = 'leaf';
        });

        return $tree;
    }
}
