<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Cart;
use JardisSupport\Validation\Tests\Support\Item;
use JardisSupport\Validation\Tests\Support\Part;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Opt-in list index path: objects inside lists are keyed "{shortName}[{index}]"
 * in the error tree and the kinds tree; the default stays the merged historic shape.
 */
final class ObjectValidatorListIndexTest extends TestCase
{
    private function registry(): ValidatorRegistry
    {
        // Reports 'name is blank' (kind missing) for any object whose $name is ''.
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

    public function testErrorOnlyInSecondSiblingCarriesIndexOne(): void
    {
        $cart = new Cart([new Item('ok'), new Item('')]);

        $result = (new ObjectValidator($this->registry(), null, true))->validate($cart);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            ['cart' => ['item[1]' => ['name' => ['name is blank']]]],
            $result->getErrors()
        );
    }

    public function testSiblingsWithErrorsStaySeparate(): void
    {
        $cart = new Cart([new Item(''), new Item('ok'), new Item('')]);

        $errors = (new ObjectValidator($this->registry(), null, true))->validate($cart)->getErrors();

        $this->assertSame(['item[0]', 'item[2]'], array_keys($errors['cart']));
    }

    public function testNestedListsProduceNestedIndexPath(): void
    {
        $cart = new Cart([
            new Item('ok'),
            new Item('ok'),
            new Item('ok', [new Part('ok'), new Part('ok'), new Part('')]),
        ]);

        $errors = (new ObjectValidator($this->registry(), null, true))->validate($cart)->getErrors();

        $this->assertSame(
            ['cart' => ['item[2]' => ['part[2]' => ['name' => ['name is blank']]]]],
            $errors
        );
    }

    public function testListOfListsAppendsIndexSegments(): void
    {
        $cart = new Cart([[new Item('ok'), new Item('')]]);

        $errors = (new ObjectValidator($this->registry(), null, true))->validate($cart)->getErrors();

        $this->assertSame(
            ['cart' => ['item[0][1]' => ['name' => ['name is blank']]]],
            $errors
        );
    }

    public function testStringKeysOfMapsAreUsedAsIndex(): void
    {
        $cart = new Cart(['a' => new Item('ok'), 'b' => new Item('')]);

        $errors = (new ObjectValidator($this->registry(), null, true))->validate($cart)->getErrors();

        $this->assertSame(['cart' => ['item[b]' => ['name' => ['name is blank']]]], $errors);
    }

    public function testEmptyListYieldsNoErrors(): void
    {
        $result = (new ObjectValidator($this->registry(), null, true))->validate(new Cart([]));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
        $this->assertSame([], $result->getKinds());
    }

    public function testKindsTreeIsStructurallyEqualToErrorTree(): void
    {
        $cart = new Cart([new Item('ok'), new Item('ok', [new Part(''), new Part('ok')]), new Item('')]);

        $result = (new ObjectValidator($this->registry(), null, true))->validate($cart);

        $this->assertSame(
            ['cart' => [
                'item[1]' => ['part[0]' => ['name' => ['name is blank']]],
                'item[2]' => ['name' => ['name is blank']],
            ]],
            $result->getErrors()
        );
        $this->assertSame(
            ['cart' => [
                'item[1]' => ['part[0]' => ['name' => [ValidationResult::KIND_MISSING]]],
                'item[2]' => ['name' => [ValidationResult::KIND_MISSING]],
            ]],
            $result->getKinds()
        );
    }

    public function testDefaultWithoutOptInKeepsHistoricMergedResult(): void
    {
        $cart = new Cart([new Item(''), new Item('ok', [new Part('')]), new Item('')]);

        $default = (new ObjectValidator($this->registry()))->validate($cart);
        $explicitOff = (new ObjectValidator($this->registry(), null, false))->validate($cart);

        $this->assertSame(
            ['cart' => [
                'item' => [
                    'name' => ['name is blank', 'name is blank'],
                    'part' => ['name' => ['name is blank']],
                ],
            ]],
            $default->getErrors()
        );
        $this->assertSame($default->getErrors(), $explicitOff->getErrors());
        $this->assertSame($default->getKinds(), $explicitOff->getKinds());
    }
}
