<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Fixture: list element that itself holds a (nested) list of Part objects.
 */
final class Item
{
    /**
     * @param array<int|string, Part> $parts
     */
    public function __construct(
        public string $name,
        public array $parts = []
    ) {
    }
}
