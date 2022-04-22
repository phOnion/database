<?php

namespace Onion\Framework\Database\ORM\Annotations;

use Attribute;
use Onion\Framework\Database\DBAL\Types\JoinDirection;
use Onion\Framework\Database\DBAL\Types\JoinType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Join
{
    public function __construct(
        public readonly string $target,
        public readonly string $columnName,
        public readonly ?string $referencedBy = null,
        public readonly ?JoinType $type = JoinType::INNER,
        public readonly ?JoinDirection $direction = null,
        public readonly bool $owned = true,
        public readonly bool $single = false,
    ) {
    }
}
