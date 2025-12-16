<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Internal;

use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\Internal\FieldBuilder;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Range;
use PHPUnit\Framework\TestCase;

final class FieldBuilderTest extends TestCase
{
    public function testValidatesAddsValidatorToComposite(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('email', $composite);

        $returnValue = $builder->validates(Email::class);

        $this->assertInstanceOf(FieldBuilder::class, $returnValue);
    }

    public function testValidatesWithOptions(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('age', $composite);

        $returnValue = $builder->validates(Range::class, ['min' => 18, 'max' => 120]);

        $this->assertInstanceOf(FieldBuilder::class, $returnValue);
    }

    public function testBreaksOnAddsBreakValidator(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('critical', $composite);

        $returnValue = $builder->breaksOn(Email::class);

        $this->assertInstanceOf(FieldBuilder::class, $returnValue);
    }

    public function testEndReturnsComposite(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('email', $composite);

        $returnValue = $builder->end();

        $this->assertSame($composite, $returnValue);
    }

    public function testChainMultipleValidators(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('password', $composite);

        $result = $builder
            ->validates(Email::class)
            ->validates(Range::class, ['min' => 8]);

        $this->assertInstanceOf(FieldBuilder::class, $result);
    }

    public function testFieldMethodReturnsNewBuilder(): void
    {
        $composite = new CompositeFieldValidator();
        $builder1 = new FieldBuilder('email', $composite);
        $builder1->validates(Email::class);

        $builder2 = $builder1->field('age');

        $this->assertInstanceOf(FieldBuilder::class, $builder2);
        $this->assertNotSame($builder1, $builder2);
    }

    public function testIntegrationWithCompositeValidator(): void
    {
        $composite = new CompositeFieldValidator();

        $composite
            ->field('email')
                ->validates(Email::class)
                ->validates(Range::class)
            ->field('age')
                ->validates(Range::class, ['min' => 18]);

        $data = new class {
            public function email(): string
            {
                return 'test@example.com';
            }

            public function age(): int
            {
                return 25;
            }
        };

        $result = $composite->validate($data);
        $this->assertTrue($result->isValid());
    }

    public function testExcludeFieldsReturnsComposite(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('email', $composite);

        $returnValue = $builder->excludeFields(['email']);

        $this->assertSame($composite, $returnValue);
    }

    public function testGetFieldNameReturnsCorrectName(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('testField', $composite);

        $this->assertSame('testField', $builder->getFieldName());
    }

    public function testGetValidatorConfigsReturnsArray(): void
    {
        $composite = new CompositeFieldValidator();
        $builder = new FieldBuilder('email', $composite);

        $builder->validates(Email::class, ['option' => 'value']);

        $configs = $builder->getValidatorConfigs();

        $this->assertIsArray($configs);
        $this->assertCount(1, $configs);
        $this->assertSame(Email::class, $configs[0]['class']);
        $this->assertSame(['option' => 'value'], $configs[0]['args']);
    }
}
