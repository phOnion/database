<?php

declare(strict_types=1);

namespace Onion\Framework\Database\ORM\Interfaces;

use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;

interface RepositoryInterface
{
    public function findById(string | int $id): ?EntityInterface;
    public function findOneBy(string | array | ExpressionInterface $criteria): ?EntityInterface;
    public function findBy(string | array | ExpressionInterface $criteria): ?CollectionInterface;
    public function findAll(): ?CollectionInterface;
}
