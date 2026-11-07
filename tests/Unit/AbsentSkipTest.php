<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit;

use JardisSupport\Contract\Data\Absent;
use JardisSupport\Contract\Validation\ValidationResult;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Tests\Support\Draft;
use JardisSupport\Validation\Validator\ChildPresence;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Length;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Update contract: a field the caller did not deliver (Absent::Value) produces no finding,
 * a delivered null stays a checked value.
 */
final class AbsentSkipTest extends TestCase
{
    private function draftValidator(): CompositeFieldValidator
    {
        $validator = new CompositeFieldValidator();
        $validator->field('name')->validates(NotBlank::class)->validates(Length::class, ['min' => 3]);
        $validator->field('email')->validates(Email::class);
        $validator->field('tags')->each(NotBlank::class);
        $validator->field('child')->validates(ChildPresence::class, ChildPresence::requiredChild('x'));

        return $validator;
    }

    private function registry(): ValidatorRegistry
    {
        return (new ValidatorRegistry())->register(Draft::class, $this->draftValidator());
    }

    // --- Zusage 1: Normal-, Break- und Each-Regeln laufen nicht ---

    public function testAbsentFieldProducesNoFindingForNormalRules(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: Absent::Value, email: Absent::Value));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
        $this->assertSame([], $result->getKinds());
    }

    public function testAbsentFieldProducesNoMissingFinding(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: Absent::Value));

        $this->assertArrayNotHasKey('name', $result->getKinds());
    }

    public function testAbsentFieldDoesNotTriggerBreakRule(): void
    {
        $validator = new CompositeFieldValidator();
        $validator->field('name')->breaksOn(Length::class, ['min' => 3]);
        $validator->field('email')->validates(Email::class);

        $result = $validator->validate(new Draft(name: Absent::Value, email: 'not-an-email'));

        $this->assertArrayHasKey('email', $result->getErrors(), 'Absent break field must not stop the run');
    }

    public function testDeliveredNullTriggersBreakRule(): void
    {
        $validator = new CompositeFieldValidator();
        $validator->field('name')->breaksOn(NotBlank::class);
        $validator->field('email')->validates(Email::class);

        $result = $validator->validate(new Draft(name: null, email: 'not-an-email'));

        $this->assertSame([], $result->getErrors(), 'null break field stops the run');
    }

    public function testAbsentListFieldSkipsEachRules(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: 'abc', tags: Absent::Value));

        $this->assertTrue($result->isValid());
    }

    public function testAbsentElementInsideListIsSkippedOthersAreChecked(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: 'abc', tags: ['a', Absent::Value, null]));

        $this->assertSame(['tags[2]'], array_keys($result->getErrors()));
    }

    public function testOtherFieldsAreStillValidatedNextToAbsentOne(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: Absent::Value, email: 'broken'));

        $this->assertSame(['email'], array_keys($result->getErrors()));
        $this->assertSame(ValidationResult::KIND_INVALID, $result->getKinds()['email'][0]);
    }

    // --- Zusage 4: null bleibt geprüft ---

    public function testDeliveredNullIsStillCheckedAndReportedMissing(): void
    {
        $result = $this->draftValidator()->validate(new Draft(name: null));

        $this->assertArrayHasKey('name', $result->getErrors());
        $this->assertSame(ValidationResult::KIND_MISSING, $result->getKinds()['name'][0]);
    }

    // --- Zusage 5: Regel, die ein anderes Feld liest (excludeFields liest das Identitätsfeld) ---

    public function testAbsentIdentityFieldCountsAsNotGiven(): void
    {
        $validator = (new CompositeFieldValidator())->excludeFields(['name']);
        $validator->field('name')->validates(NotBlank::class);

        $result = $validator->validate(new Draft(id: Absent::Value, name: null));

        $this->assertTrue($result->isValid(), 'id not given = create: excluded field is skipped');
    }

    public function testDeliveredIdentityStillValidatesExcludedField(): void
    {
        $validator = (new CompositeFieldValidator())->excludeFields(['name']);
        $validator->field('name')->validates(NotBlank::class);

        $result = $validator->validate(new Draft(id: 5, name: null));

        $this->assertFalse($result->isValid());
    }

    // --- Zusage 2 und 3: ObjectValidator ---

    /**
     * @return array<string, array{bool, bool}>
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
    public function testObjectValidatorDoesNotDescendIntoAbsentProperty(bool $indexed, bool $paths): void
    {
        $validator = new ObjectValidator($this->registry(), null, $indexed, $paths);

        $result = $validator->validate(new Draft(name: 'abc', child: Absent::Value));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getErrors());
    }

    /**
     * @dataProvider modes
     */
    public function testObjectValidatorDoesNotValidateAbsentAsNestedObject(bool $indexed, bool $paths): void
    {
        // A validator registered for any object that would report a finding when reached.
        $trap = new class implements \JardisSupport\Contract\Validation\ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['trap' => ['reached']], ['trap' => [ValidationResult::KIND_INVALID]]);
            }
        };
        $registry = (new ValidatorRegistry())->register(\UnitEnum::class, $trap);
        $validator = new ObjectValidator($registry, null, $indexed, $paths);

        $result = $validator->validate(new Draft(child: Absent::Value));

        $this->assertTrue($result->isValid(), 'Absent is a state, not a child object');
    }

    /**
     * @dataProvider modes
     */
    public function testObjectValidatorSkipsAbsentInNestedObjectAndListElement(bool $indexed, bool $paths): void
    {
        $trap = new class implements \JardisSupport\Contract\Validation\ValidatorInterface {
            public function validate(object $data): ValidationResult
            {
                return new ValidationResult(['trap' => ['reached']], ['trap' => [ValidationResult::KIND_INVALID]]);
            }
        };
        $registry = $this->registry()->register(\UnitEnum::class, $trap);
        $validator = new ObjectValidator($registry, null, $indexed, $paths);

        $inner = new class {
            public mixed $x = 1;
            public mixed $y = Absent::Value;
        };
        $root = new Draft(
            name: 'abc',
            child: $inner,
            parts: [new Draft(name: Absent::Value), Absent::Value, [Absent::Value]]
        );

        $result = $validator->validate($root);

        $this->assertTrue($result->isValid());
    }

    public function testObjectValidatorStillTraversesRealChildNextToAbsent(): void
    {
        $registry = $this->registry();
        $validator = new ObjectValidator($registry, null, false, true);

        $result = $validator->validate(new Draft(
            name: 'abc',
            child: Absent::Value,
            parts: [new Draft(name: 'ab')]
        ));

        $this->assertSame('parts[0]', array_key_first($result->getErrors()['draft']));
    }

    // --- Zusage 3: ChildPresence ---

    public function testChildPresenceSkipsAbsentValue(): void
    {
        $this->assertNull((new ChildPresence())->validateValue(Absent::Value, ChildPresence::requiredChild('x')));
    }

    public function testChildPresenceSkipsAbsentChildProperty(): void
    {
        $parent = new class {
            public mixed $x = Absent::Value;
        };

        $this->assertNull((new ChildPresence())->validateValue($parent, ChildPresence::requiredChild('x')));
        $this->assertNull((new ChildPresence())->validateValue([$parent], ChildPresence::requiredChild('x')));
    }

    public function testChildPresenceSkipsAbsentListElement(): void
    {
        $parent = new class {
            public mixed $x = 1;
        };

        $this->assertNull(
            (new ChildPresence())->validateValue([$parent, Absent::Value], ChildPresence::requiredChild('x'))
        );
    }

    public function testChildPresenceStillReportsNullChild(): void
    {
        $parent = new class {
            public mixed $x = null;
        };

        $this->assertNotNull((new ChildPresence())->validateValue($parent, ChildPresence::requiredChild('x')));
    }

    public function testChildPresenceViaCompositeSkipsAbsentParentAndReportsNullChild(): void
    {
        $nullChild = new class {
            public mixed $x = null;
        };

        $validator = new CompositeFieldValidator();
        $validator->field('child')->validates(ChildPresence::class, ChildPresence::requiredChild('x'));

        $this->assertTrue($validator->validate(new Draft(child: Absent::Value))->isValid());
        $this->assertSame(
            ValidationResult::KIND_MISSING,
            $validator->validate(new Draft(child: $nullChild))->getKinds()['child'][0]
        );
    }
}
