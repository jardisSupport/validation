<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Fixture: root object holding a list of Item objects.
 */
final class Cart
{
    /**
     * @param array<int|string, Item> $items
     */
    public function __construct(
        public array $items
    ) {
    }
}
