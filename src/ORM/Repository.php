<?php

namespace Onion\Framework\Database\ORM;

use DateTimeImmutable;
use InvalidArgumentException;

use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\DataTypes;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\ORM\Interfaces\EntityInterface;
use Onion\Framework\Database\ORM\Interfaces\EntityManagerInterface;
use Onion\Framework\Database\DBAL\Parameter;
use Onion\Framework\Database\ORM\Annotations\OneToMany;
use Onion\Framework\Database\ORM\Annotations\OneToOne;
use Onion\Framework\Database\ORM\Interfaces\RepositoryInterface;
use Onion\Framework\Proxy\Interfaces\ProxyFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\Uuid;


class Repository implements RepositoryInterface
{
    final public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ObjectData $data,
        private readonly ?ProxyFactoryInterface $proxyFactory = null,
        private readonly ?CacheInterface $cache = null
    ) {
    }

    public function findById(string | int $id): ?EntityInterface
    {
        return $this->findOneBy([
            $this->data->primaryKey() => $id
        ]);
    }

    public function findOneBy(string | array | ExpressionInterface $criteria): ?EntityInterface
    {
        return $this->findBy($criteria, 1, 0)?->current();
    }

    public function findAll(): CollectionInterface
    {
        return $this->findBy([]);
    }

    public function findBy(
        array | string | ExpressionInterface $criteria,
        ?int $limit = null,
        ?int $offset = null
    ): ?CollectionInterface {
        $builder = $this->em->getConnection()->createQueryBuilder();
        $select = $builder->select()
            ->from($this->data->getTableName());

        $tableAlias = $this->data->getTableAlias();

        foreach ($this->data->getColumns() as $prop => $column) {
            if ($column !== null) {
                $select->column("{$tableAlias}.{$column->name}", $prop);
            }
        }

        if ($limit !== null) $select->limit($limit);
        if ($offset !== null) $select->offset($offset);


        $parameters = [];
        if (is_array($criteria)) {
            $where = [];
            $expr = $this->em->getConnection()->createExpressionBuilder();

            foreach ($criteria as $key => $value) {
                $field = $this->data->getColumnData($key)?->name ?? null;
                $type = $this->data->getColumnType($key);

                assert($field !== null, new InvalidArgumentException(
                    "No column definition for field '{$key}', did you forget to add #[Column()] on {$this->data->entityClass}"
                ));
                assert($type !== null, new InvalidArgumentException(
                    "No type is known for field '{$key}', did you forget to add #[Type()]?"
                ));

                $where[] = $expr->eq($field, ":{$field}");
                $parameters[] = new Parameter(
                    $field,
                    $value,
                    $type,
                );
            }

            $criteria = empty($criteria) ? null : $expr->and(...$where);
        }

        if ($criteria) {
            $select->where((string) $criteria);
        }

        $result = $this->em->getConnection()
            ->execute(
                $builder->getSql($select),
                $parameters
            );

        return $result->items()->map(function ($row) {
            $methods = [];
            foreach ($row as $key => $value) {
                if ($this->data->isJoin($key)) {
                    $join = $this->data->getJoinData($key);

                    if ($join->single || $join instanceof OneToOne) {
                        $methods[$key] = fn () => $this->em->findBy($join->target, [
                            $join->columnName => $value,
                        ])?->current();
                    } else if (!$join->single || $join instanceof OneToMany) {
                        $methods[$key] = fn () => $this->em->findBy($join->target, [
                            $join->columnName => $value,
                        ]);
                    }
                }

                $row[$key] = $this->convertRowData(
                    $this->data->getColumnType($key),
                    $value
                );
            }

            $data = $this->data;
            return $row !== null ? $this->proxyFactory->generate(
                $this->data->entityClass,
                fn () => (new $data->entityClass)->hydrate($row),
                [],
                $methods
            ) : null;
        });
    }

    private function convertRowData(DataTypes $type, mixed $value): mixed
    {
        return match ($type) {
            DataTypes::JSON => json_decode($value, true),
            DataTypes::DATETIME => new DateTimeImmutable($value),
            DataTypes::TIMESTAMP => new DateTimeImmutable($value),
            DataTypes::UUID => Uuid::fromString($value),
            default => $value,
        };
    }
}
