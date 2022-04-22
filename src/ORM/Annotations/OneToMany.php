<?php

declare(strict_types=1);

namespace Onion\Framework\Database\ORM\Annotations;

use Attribute;
use Onion\Framework\Database\DBAL\Types\JoinDirection;
use Onion\Framework\Database\DBAL\Types\JoinType;
use Onion\Framework\Database\ORM\Annotations\Join;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany extends Join
{
    public function __construct(
        string $target,
        string $columnName,
        ?string $referencedBy = null,
        ?JoinType $type = JoinType::INNER,
        ?JoinDirection $direction = JoinDirection::LEFT,
        bool $owned = true,
    ) {
        parent::__construct(
            target: $target,
            columnName: $columnName,
            referencedBy: $referencedBy,
            type: $type,
            direction: $direction,
            single: false,
            owned: $owned,
        );
    }
}
