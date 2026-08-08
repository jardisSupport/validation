<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Tests\Support;

/**
 * Named fixture holding a property whose name equals the lcfirst short class
 * name of its value ("link" holds a Link instance).
 */
final class Branch
{
    public function __construct(
        public Link $link
    ) {
    }
}
