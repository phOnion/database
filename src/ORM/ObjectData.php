<?php

namespace Onion\Framework\Database\ORM;

use Onion\Framework\Database\DataTypes;
use Onion\Framework\Database\ORM\Annotations\Column;
use Onion\Framework\Database\ORM\Annotations\Entity;
use Onion\Framework\Database\ORM\Annotations\GeneratedValue;
use Onion\Framework\Database\ORM\Annotations\Join;
use Onion\Framework\Database\ORM\Annotations\PrimaryKey;
use Onion\Framework\Database\ORM\Annotations\Type;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

class ObjectData
{
    private readonly Entity $entity;
    private readonly string $primaryKey;

    private array $columnTypes = [];
    private array $columns = [];
    private array $joins = [];

    private array $fillableFields = [];

    public function __construct(
        public readonly string $entityClass,
        \ReflectionClass $reflection,
    ) {
        $entityAttributes = $reflection->getAttributes(Entity::class);
        if (!empty($entityAttributes)) {
            $this->entity = current($entityAttributes)->newInstance();
        }

        foreach ($reflection->getProperties() as $property) {
            [$column, $generated, $primaryKey, $type] = $this->handleColumn($property);

            $this->columns[$property->getName()] = $column;
            $this->columnTypes[$property->getName()] = $type;
            if (!($generated)) {
                $this->fillableFields[$property->getName()] = $column?->name;
            }

            if ($primaryKey) {
                if (isset($this->primaryKey)) {
                    throw new \LogicException("Entity '{$entityClass}' must have only 1 primary key");
                }

                $this->primaryKey = $property->getName();
            }

            $join = $this->handleJoin($property);
            if ($join) {
                /** patch around the missing column */
                if (!isset($this->columns[$property->getName()])) {
                    $this->columns[$property->getName()] = new Column($join->referencedBy);
                }
                // $this->columnTypes[$property->getName()] = $this->handleType($property)?->type ?? DataTypes::TEXT;
                $this->joins[$property->getName()] = $join;
            }
        }
    }

    private function handleType(ReflectionProperty $prop)
    {
        return ($prop->getAttributes(Type::class)[0] ?? null)?->newInstance();
    }

    private function handleColumn(ReflectionProperty $prop)
    {
        $generated = ($prop->getAttributes(GeneratedValue::class)[0] ?? null)?->newInstance();
        $column = ($prop->getAttributes(Column::class)[0] ?? null)?->newInstance();

        return [
            $column ?? null,
            $generated,
            !!($prop->getAttributes(PrimaryKey::class)[0] ?? false),
            $this->handleType($prop)?->type ?? DataTypes::TEXT
        ];
    }

    private function handleJoin(ReflectionProperty $prop): ?Join
    {
        return ($prop->getAttributes(Join::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance();
    }

    public function primaryKey(): ?string
    {
        return $this->primaryKey ?? null;
    }

    public function getRepositoryName(): ?string
    {
        return $this->entity->getRepositoryName;
    }

    public function getTableAlias(): string
    {
        return $this->entity->alias ?? $this->entity->table;
    }

    public function getTableName(): string
    {
        return $this->entity->table;
    }


    public function isJoin(string $prop)
    {
        return isset($this->joins[$prop]) && $this->joins[$prop] !== null;
    }

    public function getColumnType(string $prop): ?DataTypes
    {
        return $this->columnTypes[$prop] ?? null;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function findColumnField(string $columnName): ?string
    {
        foreach ($this->columns as $field => $column) {
            /** @var Column $column */
            if ($column->name === $columnName) {
                return $field;
            }
        }

        return null;
    }

    public function findColumn(string $columnName): Column
    {
        $key = $this->findColumnField($columnName);
        return $key !== null ? $this->columns[$key] : null;
    }

    public function getColumnData(string $propertyName): ?Column
    {
        return $this->columns[$propertyName] ?? null;
    }

    /** @return Column[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getJoinData(string $propertyName): ?Join
    {
        return $this->joins[$propertyName] ?? null;
    }

    public function getFillableFields(): array
    {
        return array_filter($this->fillableFields, fn (?string $field) => $field !== null);
    }

    public static function getEntityMetadata(string $targetClass): ObjectData
    {
        return new static($targetClass, new ReflectionClass($targetClass));
    }
}
