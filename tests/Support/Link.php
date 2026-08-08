<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Named fixture whose lcfirst short class name ("link") collides with the
 * parent field name in key collision tests.
 */
final class Link
{
    public function __construct(
        public string $item = ''
    ) {
    }
}
