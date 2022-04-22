<?php

namespace Onion\Framework\Database\ORM\Annotations;

use Attribute;
use Onion\Framework\Annotations\Interfaces\AnnotationInterface;
use Onion\Framework\Database\Repository;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity implements AnnotationInterface
{
    public function __construct(
        public readonly string $table,
        public readonly string $repository = Repository::class,
        public readonly ?string $alias = null,
    ) {
    }
}
