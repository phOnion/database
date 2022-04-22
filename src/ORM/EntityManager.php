<?php

namespace Onion\Framework\Database\ORM;

use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\Connection;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\ORM\Interfaces\EntityInterface;
use Onion\Framework\Database\ORM\Interfaces\EntityManagerInterface;
use Onion\Framework\Database\ORM\UnitOfWork;
use ReflectionClass;
use WeakMap;
use Onion\Framework\Database\ORM\Repository;
use Onion\Framework\Proxy\Interfaces\ProxyFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use WeakReference;

class EntityManager implements EntityManagerInterface
{
    /** @var WeakReference[] */
    private array $objectDataMapping = [];
    private readonly WeakMap $repositories;

    public function __construct(
        public readonly Connection $connection,
        private ?UnitOfWork $uow = null,
        private ?ProxyFactoryInterface $proxyFactory = null,
        private ?CacheInterface $cache = null,
    ) {
        $this->uow ??= new UnitOfWork($connection);
        $this->repositories = new WeakMap();
    }

    private function getMetadata(string $entity): ObjectData
    {
        $objectData = ($this->objectDataMapping[$entity] ?? null)?->get() ??
            new ObjectData($entity, new ReflectionClass($entity));


        if (!isset($this->objectDataMapping[$entity])) {
            $this->objectDataMapping[$entity] = WeakReference::create($objectData);
        }

        return $objectData;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getRepository(string $entity): Repository
    {
        /** @var ObjectData $objectData */
        $objectData = $this->getMetadata($entity);

        if (isset($this->repositories[$objectData])) {
            return $this->repositories[$objectData];
        }

        $repository = new Repository($this, $objectData, $this->proxyFactory, $this->cache);

        if (!isset($this->repositories[$objectData])) {
            $this->repositories[$objectData] = $repository;
        }

        return $repository;
    }

    public function persist(EntityInterface $entity): void
    {
        $this->uow->add($entity, $this->getMetadata($entity::class));
    }

    public function update(EntityInterface $entity): void
    {
        $this->uow->update($entity, $this->getMetadata($entity::class));
    }

    public function delete(EntityInterface $entity): void
    {
        $this->uow->remove($entity, $this->getMetadata($entity::class));
    }

    public function flush(): void
    {
        $this->uow->commit();
    }

    public function findBy(
        string $entityClass,
        array | string | ExpressionInterface $criteria,
        ?int $limit = null,
        ?int $offset = null
    ): ?CollectionInterface {
        return $this->getRepository($entityClass)->findBy($criteria, $limit, $offset);
    }
}
