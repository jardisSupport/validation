<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Fixture: leaf element of a nested list.
 */
final class Part
{
    public function __construct(
        public string $name
    ) {
    }
}
