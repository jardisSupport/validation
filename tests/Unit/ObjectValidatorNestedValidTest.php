<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Cart;
use JardisSupport\Validation\Tests\Support\Crate;
use JardisSupport\Validation\Tests\Support\Item;
use JardisSupport\Validation\Tests\Support\Part;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * A graph whose nodes are all valid is valid at any depth and in every mode:
 * nested empty error arrays (one per valid list element / child) must not
 * count as errors.
 */
final class ObjectValidatorNestedValidTest extends TestCase
{
    private function registry(): ValidatorRegistry
    {
        $nameValidator = new class implements ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                if ($data->name !== '') {
                    return new ValidationResult();
                }

                return new ValidationResult(
                    ['name' => ['name is blank']],
                    ['name' => [ValidationResult::KIND_MISSING]]
                );
            }
        };

        return (new ValidatorRegistry())
            ->register(Item::class, $nameValidator)
            ->register(Part::class, $nameValidator);
    }

    /**
     * @return array<string, array{0: bool, 1: bool}> [indexedLists, fieldPaths]
     */
    public static function modes(): array
    {
        return [
            'default' => [false, false],
            'indexedLists' => [true, false],
            'fieldPaths' => [false, true],
        ];
    }

    /**
     * @dataProvider modes
     */
    public function testValidListOfValidChildrenIsValid(bool $indexedLists, bool $fieldPaths): void
    {
        $validator = new ObjectValidator($this->registry(), null, $indexedLists, $fieldPaths);

        $result = $validator->validate(new Crate(items: [new Item('a'), new Item('b')]));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
        $this->assertSame([], $result->getKinds());
        $this->assertSame([], $result->toFields());
    }

    /**
     * @dataProvider modes
     */
    public function testValidNestedListsAtDepthAreValid(bool $indexedLists, bool $fieldPaths): void
    {
        $validator = new ObjectValidator($this->registry(), null, $indexedLists, $fieldPaths);
        $cart = new Cart([new Item('a', [new Part('p1'), new Part('p2')]), new Item('b', [new Part('p3')])]);

        $result = $validator->validate($cart);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
        $this->assertSame([], $result->getKinds());
    }

    /**
     * @dataProvider modes
     */
    public function testValidSingleChildObjectIsValid(bool $indexedLists, bool $fieldPaths): void
    {
        $validator = new ObjectValidator($this->registry(), null, $indexedLists, $fieldPaths);

        $result = $validator->validate(new Crate(featured: new Item('a', [new Part('p')])));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
    }

    public function testOneInvalidChildKeepsGraphInvalidAndNamesItsPath(): void
    {
        $validator = new ObjectValidator($this->registry(), fieldPaths: true);

        $result = $validator->validate(new Crate(items: [new Item('a'), new Item(''), new Item('c')]));

        $this->assertFalse($result->isValid());
        $this->assertSame(['crate' => ['items[1]' => ['name' => ['name is blank']]]], $result->getErrors());
        $this->assertSame(
            [['field' => 'items[1].name', 'reason' => 'missing', 'message' => 'name is blank']],
            $result->toFields('crate')
        );
    }

    public function testInvalidGrandchildKeepsGraphInvalidAndNamesItsPath(): void
    {
        $validator = new ObjectValidator($this->registry(), fieldPaths: true);

        $result = $validator->validate(new Crate(items: [new Item('a', [new Part('p'), new Part('')])]));

        $this->assertFalse($result->isValid());
        $this->assertSame(
            [['field' => 'items[0].parts[1].name', 'reason' => 'missing', 'message' => 'name is blank']],
            $result->toFields('crate')
        );
    }
}
