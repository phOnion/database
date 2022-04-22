<?php

namespace Onion\Framework\Database\DBAL;

use Onion\Framework\Database\DataTypes;

class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        public readonly DataTypes $type,
    ) {
    }
}
