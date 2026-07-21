<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Unit\Internal;

use JardisSupport\Validation\Internal\ValidationContext;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ValidationContext visit tracking and depth limiting.
 */
final class ValidationContextTest extends TestCase
{
    public function testHasVisitedReturnsFalseForNewObject(): void
    {
        $context = new ValidationContext();
        $object = new \stdClass();

        $this->assertFalse($context->hasVisited($object));
    }

    public function testMarkVisitedAndHasVisited(): void
    {
        $context = new ValidationContext();
        $object = new \stdClass();

        $context->markVisited($object);
        $this->assertTrue($context->hasVisited($object));
    }

    public function testDifferentObjectsTrackedSeparately(): void
    {
        $context = new ValidationContext();
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $context->markVisited($obj1);

        $this->assertTrue($context->hasVisited($obj1));
        $this->assertFalse($context->hasVisited($obj2));
    }

    public function testEnterAndExitLevel(): void
    {
        $context = new ValidationContext();

        // Should not throw at normal depth
        $context->enterLevel();
        $context->enterLevel();
        $context->exitLevel();
        $context->exitLevel();

        // If we got here without exception, depth tracking works
        $this->assertTrue(true);
    }

    public function testMaxDepthExceeded(): void
    {
        $maxDepth = 3;
        $context = new ValidationContext($maxDepth);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum validation depth');

        for ($i = 0; $i <= $maxDepth; $i++) {
            $context->enterLevel();
        }
    }

    public function testMaxDepthExactBoundary(): void
    {
        $maxDepth = 5;
        $context = new ValidationContext($maxDepth);

        // Entering exactly maxDepth levels should work
        for ($i = 0; $i < $maxDepth; $i++) {
            $context->enterLevel();
        }

        // One more should throw
        $this->expectException(\RuntimeException::class);
        $context->enterLevel();
    }

    public function testExitLevelAllowsReentry(): void
    {
        $maxDepth = 2;
        $context = new ValidationContext($maxDepth);

        $context->enterLevel();
        $context->enterLevel();
        $context->exitLevel();

        // Should be able to enter again after exiting
        $context->enterLevel();

        // Should still throw at max+1
        $this->expectException(\RuntimeException::class);
        $context->enterLevel();
    }

    public function testExitLevelDoesNotUnderflow(): void
    {
        $context = new ValidationContext(2);

        // Exit without entering — depth must not go negative
        $context->exitLevel();
        $context->exitLevel();

        // If depth went negative, we could enter 4 times without exception
        // With the guard, entering 3 times must still throw at maxDepth=2
        $context->enterLevel();
        $context->enterLevel();

        $this->expectException(\RuntimeException::class);
        $context->enterLevel();
    }

    public function testDefaultMaxDepth(): void
    {
        $context = new ValidationContext();

        // Default max depth is 100, entering 100 levels should work
        for ($i = 0; $i < 100; $i++) {
            $context->enterLevel();
        }

        $this->expectException(\RuntimeException::class);
        $context->enterLevel();
    }
}
