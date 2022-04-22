<?php

declare(strict_types=1);

namespace Onion\Framework\Database\ORM\Interfaces;

use Onion\Framework\Database\ORM\ObjectData;

interface UnitOfWorkInterface
{
    public function add(EntityInterface $entity, ObjectData $objectData): void;
    public function remove(EntityInterface $entity, ObjectData $objectData): void;
    public function update(EntityInterface $entity, ObjectData $objectData): void;

    public function commit(): void;
}
