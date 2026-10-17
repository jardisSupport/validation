<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Fixture: root object with two properties of the same item class, a list of
 * items, a list of lists of items and a scalar list.
 */
final class Crate
{
    /**
     * @param array<int|string, Item> $items
     * @param array<int, array<int, Item>> $grid
     * @param array<int|string, mixed> $labels
     */
    public function __construct(
        public array $items = [],
        public ?Item $featured = null,
        public ?Item $fallback = null,
        public array $grid = [],
        public array $labels = []
    ) {
    }
}
