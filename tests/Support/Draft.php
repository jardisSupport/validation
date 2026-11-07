<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Fixture: partial-write payload. A property holds a value, null (delivered, clears)
 * or Absent::Value (not delivered).
 */
final class Draft
{
    /**
     * @param array<int|string, mixed> $tags
     */
    public function __construct(
        public mixed $id = null,
        public mixed $name = null,
        public mixed $email = null,
        public mixed $child = null,
        public mixed $tags = [],
        public mixed $parts = []
    ) {
    }
}
