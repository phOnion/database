<?php

namespace Onion\Framework\Database\ORM\Annotations;

use Attribute;
use Onion\Framework\Database\DataTypes;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Type
{
    public function __construct(
        public readonly DataTypes $type,
    ) {
    }
}
