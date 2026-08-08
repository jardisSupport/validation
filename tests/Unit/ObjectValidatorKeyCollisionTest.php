<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Branch;
use JardisSupport\Validation\Tests\Support\Link;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests that string key collisions between a parent's own field errors and
 * the recursive traversal entries never lose errors. The collision occurs
 * whenever a field name equals the lcfirst short class name of the nested
 * value ("link" holds a Link instance).
 */
final class ObjectValidatorKeyCollisionTest extends TestCase
{
    public function testFieldErrorSurvivesCollisionWithEmptyTraversalEntry(): void
    {
        $branch = new Branch(new Link(''));

        $branchValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['link' => ['item can not be empty']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(Branch::class, $branchValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($branch);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertSame('item can not be empty', $errors['branch']['link'][0] ?? null);
    }

    public function testFieldErrorAndNestedErrorsAreBothKept(): void
    {
        $branch = new Branch(new Link(''));

        $branchValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['link' => ['link incomplete']]);
            }
        };
        $linkValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['item' => ['item is invalid']]);
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(Branch::class, $branchValidator);
        $registry->register(Link::class, $linkValidator);

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($branch);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertSame('link incomplete', $errors['branch']['link'][0] ?? null);
        $this->assertSame(['item is invalid'], $errors['branch']['link']['item'] ?? null);
    }

    public function testSiblingPropertiesOfSameClassKeepBothErrors(): void
    {
        $container = new class (new Link('A'), new Link('B')) {
            public function __construct(
                public readonly Link $first,
                public readonly Link $second
            ) {
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(Link::class, $this->itemEchoValidator());

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($container);

        $this->assertFalse($result->isValid());
        $rootErrors = array_values($result->getErrors())[0] ?? [];
        $this->assertIsArray($rootErrors);
        $this->assertSame(['bad A', 'bad B'], $rootErrors['link']['item'] ?? null);
    }

    public function testArrayItemsOfSameClassKeepAllErrors(): void
    {
        $container = new class ([new Link('A'), new Link('B')]) {
            /** @param array<Link> $links */
            public function __construct(
                public readonly array $links
            ) {
            }
        };

        $registry = new ValidatorRegistry();
        $registry->register(Link::class, $this->itemEchoValidator());

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($container);

        $this->assertFalse($result->isValid());
        $rootErrors = array_values($result->getErrors())[0] ?? [];
        $this->assertIsArray($rootErrors);
        $this->assertSame(['bad A', 'bad B'], $rootErrors['link']['item'] ?? null);
    }

    private function itemEchoValidator(): ValidatorInterface
    {
        return new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                $item = $data instanceof Link ? $data->item : 'unexpected';

                return new ValidationResult(['item' => ['bad ' . $item]]);
            }
        };
    }
}
