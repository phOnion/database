<?php

declare(strict_types=1);

namespace Onion\Framework\Database\ORM;

use Onion\Framework\Database\DBAL\ExpressionBuilder;
use Onion\Framework\Database\DBAL\Interfaces\OperationInterface;
use Onion\Framework\Database\DBAL\Operations\DeleteOperation;
use Onion\Framework\Database\DBAL\Operations\InsertOperation;
use Onion\Framework\Database\DBAL\Parameter;
use Onion\Framework\Database\DBAL\QueryBuilder;
use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\ORM\Annotations\Column;
use Onion\Framework\Database\ORM\Interfaces\EntityInterface;
use Onion\Framework\Database\ORM\Interfaces\UnitOfWorkInterface;
use SplPriorityQueue;
use WeakMap;
use WeakReference;
use Onion\Framework\Database\DBAL\Types\ConditionType;

class UnitOfWork implements UnitOfWorkInterface
{
    private const PRIORITY_INSERT = 30000;
    private const PRIORITY_UPDATE = 20000;
    private const PRIORITY_DELETE = 10000;

    private array $insert = [];
    private array $insertCounters = [];

    private int $updateCounter = 0;

    private array $delete = [];
    private array $deleteCounters = [];

    private readonly SplPriorityQueue $operations;
    private readonly QueryBuilder $queryBuilder;
    private readonly ExpressionBuilder $expr;
    private readonly WeakMap $parameters;

    public function __construct(
        private readonly ConnectionInterface $connection
    ) {
        $this->operations = new SplPriorityQueue();
        $this->operations->setExtractFlags(
            SplPriorityQueue::EXTR_DATA,
        );

        $this->parameters = new WeakMap();

        $this->queryBuilder = $connection->createQueryBuilder();
        $this->expr = $connection->createExpressionBuilder();
    }

    public function add(EntityInterface $entity, ObjectData $data): void
    {
        $entityClass = $entity::class;

        /** @var InsertOperation $insert */
        $insert = ($this->insert[$entityClass] ?? null)?->get() ?? null;

        if (!isset($this->insert[$entityClass])) {
            $insert = $this->queryBuilder->insert()->into($data->getEntity()->table);
            $this->insert[$entityClass] = WeakReference::create($insert);

            $this->operations->insert($insert, self::PRIORITY_INSERT - count($this->insert));

            $this->insertCounters[$entityClass] = 0;
            $this->parameters[$insert] = [];
            $insert->columns(...$data->getFillableFields());
        }


        $values = [];
        foreach ($entity->extract($data->getFillableFields()) as $name => $value) {
            /** @var Column $column */
            $column = $data->getColumnData($name);
            $param = "{$name}_{$this->insertCounters[$entityClass]}";

            $values[] = ":{$param}";

            $this->parameters[$insert][] = new Parameter($param, $value, $column->type);
        }

        $this->insertCounters[$entityClass]++;
        $insert->values(...$values);
    }

    public function remove(EntityInterface $entity, ObjectData $data): void
    {
        $entityClass = $entity::class;

        /** @var DeleteOperation $delete */
        $delete = ($this->delete[$entityClass] ?? null)?->get() ?? null;

        if (!isset($this->delete[$entityClass])) {
            $delete = $this->queryBuilder->delete()
                ->from($data->getEntity()->table);

            $this->delete[$entityClass] = WeakReference::create($delete);
            $this->deleteCounters[$entityClass] = 0;
            $this->operations->insert($delete, static::PRIORITY_DELETE - count($this->delete));
            $this->parameters[$delete] = [];
        }

        $column = $data->getColumnData($data->primaryKey());
        $idColumn = $column->name;
        $param = "{$idColumn}_{$this->deleteCounters[$entityClass]}";

        if ($this->deleteCounters[$entityClass] === 0) {
            $delete->where($this->expr->eq($idColumn, ":{$param}"));
        } else {
            $delete->where(
                $this->expr->eq($idColumn, ":{$param}"),
                ConditionType::OR,
            );
        }

        $value = $entity->extract([$data->primaryKey()])[$data->primaryKey()];

        $this->parameters[$delete][] = new Parameter(
            $param,
            $value,
            $column->type,
        );
        $this->deleteCounters[$entityClass]++;
    }

    public function update(EntityInterface $entity, ObjectData $data): void
    {
        $update = $this->queryBuilder->update()->table($data->getEntity()->table);

        $this->operations->insert($update, self::PRIORITY_UPDATE - $this->updateCounter++);

        $this->parameters[$update] = [];

        foreach ($entity->extract($data->getFillableFields()) as $name => $value) {
            /** @var Column $column */
            $column = $data->getColumnData($name);
            $update->set($this->expr->eq($column->name, ":{$column->name}"));
            $this->parameters[$update][] = new Parameter($column->name, $value, $column->type);
        }

        $idColumn = $data->getColumnData($data->primaryKey());
        $update->where($this->expr->eq($idColumn->name, ":{$idColumn->name}"));

        $this->parameters[$update][] = new Parameter(
            $idColumn->name,
            $entity->extract([$idColumn->name])[$idColumn->name],
            $idColumn->type,
        );
    }

    public function commit(): void
    {
        try {
            $this->connection->beginTransaction();

            while (!$this->operations->isEmpty()) {
                /** @var OperationInterface $operation */
                $operation = $this->operations->extract();
                $this->connection->execute(
                    $this->queryBuilder->getSql(
                        $operation,
                    ),
                    ($this->parameters[$operation] ?? []),
                );
            }

            $this->connection->commit();
        } catch (\Throwable $ex) {
            $this->connection->rollback();

            throw $ex;
        }
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }
}
