<?php

namespace Onion\Framework\Database\ORM\Annotations;

use Attribute;
use Onion\Framework\Database\DataTypes;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $name,
        public readonly bool $nullable = true,
    ) {
    }
}
