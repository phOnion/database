<?php

declare(strict_types=1);

namespace Onion\Framework\Database\ORM\Interfaces;

use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\ORM\Repository;

interface EntityManagerInterface
{
    public function getConnection(): ConnectionInterface;
    public function getRepository(string $entityClass): Repository;
    public function findBy(
        string $entityClass,
        array | string | ExpressionInterface $criteria,
        ?int $limit = null,
        ?int $offset = null,
    ): ?CollectionInterface;

    public function persist(EntityInterface $entity): void;
    public function update(EntityInterface $entity): void;
    public function delete(EntityInterface $entity): void;

    public function flush(): void;
}
