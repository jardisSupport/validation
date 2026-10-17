<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Contract\Validation\ValidatorInterface;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Crate;
use JardisSupport\Validation\Tests\Support\Item;
use JardisSupport\Validation\Tests\Support\Part;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Opt-in field path mode: every nested error is keyed by the property name of
 * its level, list elements by "{property}[{index}]"; the root keeps its short name.
 */
final class ObjectValidatorFieldPathTest extends TestCase
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

    private function validator(): ObjectValidator
    {
        return new ObjectValidator($this->registry(), fieldPaths: true);
    }

    public function testDepthOneSinglePropertyUsesPropertyName(): void
    {
        $result = $this->validator()->validate(new Crate(featured: new Item('')));

        $this->assertSame(['crate' => ['featured' => ['name' => ['name is blank']]]], $result->getErrors());
        $this->assertSame(
            ['crate' => ['featured' => ['name' => [ValidationResult::KIND_MISSING]]]],
            $result->getKinds()
        );
    }

    public function testDepthOneListElementUsesPropertyNameAndIndex(): void
    {
        $result = $this->validator()->validate(new Crate(items: [new Item('ok'), new Item('')]));

        $this->assertSame(['crate' => ['items[1]' => ['name' => ['name is blank']]]], $result->getErrors());
    }

    public function testFieldListViaValidatorInterfaceNamesListElementField(): void
    {
        $validator = $this->validator();
        $validate = static fn(ValidatorInterface $v, object $data): ValidationResult => $v->validate($data);

        $result = $validate($validator, new Crate(items: [new Item('ok'), new Item('')]));

        $this->assertSame(
            [['field' => 'items[1].name', 'reason' => 'missing', 'message' => 'name is blank']],
            $result->toFields('crate')
        );
    }

    public function testDepthTwoListInsideListElement(): void
    {
        $crate = new Crate(items: [
            new Item('ok'),
            new Item('ok', [new Part('ok'), new Part('ok'), new Part('')]),
        ]);

        $result = $this->validator()->validate($crate);

        $this->assertSame(
            ['crate' => ['items[1]' => ['parts[2]' => ['name' => ['name is blank']]]]],
            $result->getErrors()
        );
        $this->assertSame(
            [['field' => 'items[1].parts[2].name', 'reason' => 'missing', 'message' => 'name is blank']],
            $result->toFields('crate')
        );
    }

    public function testDepthThreeWithListsInLists(): void
    {
        $crate = new Crate(grid: [
            [new Item('ok')],
            [new Item('ok'), new Item('', [new Part('ok'), new Part('')])],
        ]);

        $result = $this->validator()->validate($crate);

        $this->assertSame(
            ['crate' => ['grid[1][1]' => [
                'name' => ['name is blank'],
                'parts[1]' => ['name' => ['name is blank']],
            ]]],
            $result->getErrors()
        );
        $this->assertSame(
            ['grid[1][1].name', 'grid[1][1].parts[1].name'],
            array_column($result->toFields('crate'), 'field')
        );
    }

    public function testTwoPropertiesOfSameClassStaySeparate(): void
    {
        $result = $this->validator()->validate(new Crate(featured: new Item(''), fallback: new Item('')));

        $this->assertSame(
            ['crate' => [
                'featured' => ['name' => ['name is blank']],
                'fallback' => ['name' => ['name is blank']],
            ]],
            $result->getErrors()
        );
    }

    public function testDefaultModeKeepsShortClassNameShape(): void
    {
        $result = (new ObjectValidator($this->registry()))
            ->validate(new Crate(featured: new Item(''), fallback: new Item('')));

        $this->assertSame(
            ['crate' => ['item' => ['name' => ['name is blank', 'name is blank']]]],
            $result->getErrors()
        );
    }

    public function testScalarListElementErrorIsKeyedByIndex(): void
    {
        $crateValidator = (new CompositeFieldValidator())
            ->field('labels')->each(Length::class, Length::min(3))
            ->end();
        $registry = (new ValidatorRegistry())->register(Crate::class, $crateValidator);

        $result = (new ObjectValidator($registry, fieldPaths: true))
            ->validate(new Crate(labels: ['abc', 'x', 'defg']));

        $this->assertSame(['crate'], array_keys($result->getErrors()));
        $this->assertSame(['labels[1]'], array_keys($result->getErrors()['crate']));
        $this->assertSame(
            ['crate' => ['labels[1]' => [ValidationResult::KIND_INVALID]]],
            $result->getKinds()
        );
        $this->assertSame('labels[1]', $result->toFields('crate')[0]['field']);
        $this->assertSame('invalid', $result->toFields('crate')[0]['reason']);
    }

    public function testEmptyScalarListProducesNoError(): void
    {
        $crateValidator = (new CompositeFieldValidator())
            ->field('labels')->each(Length::class, Length::min(3))
            ->end();
        $registry = (new ValidatorRegistry())->register(Crate::class, $crateValidator);

        $result = (new ObjectValidator($registry, fieldPaths: true))->validate(new Crate(labels: []));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
    }

    public function testElementPresenceValidatorReportsKindMissingPerIndex(): void
    {
        $crateValidator = (new CompositeFieldValidator())
            ->field('labels')->each(NotBlank::class)
            ->end();

        $result = $crateValidator->validate(new Crate(labels: ['a', null, 'b', null]));

        $this->assertSame(['labels[1]', 'labels[3]'], array_keys($result->getErrors()));
        $this->assertSame(
            ['labels[1]' => [ValidationResult::KIND_MISSING], 'labels[3]' => [ValidationResult::KIND_MISSING]],
            $result->getKinds()
        );
    }

    public function testElementValidatorSkipsNonListValue(): void
    {
        $crateValidator = (new CompositeFieldValidator())
            ->field('featured')->each(NotBlank::class)
            ->end();

        $this->assertTrue($crateValidator->validate(new Crate())->isValid());
    }
}
