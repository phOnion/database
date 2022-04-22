<?php

declare(strict_types=1);

namespace Onion\Framework\Database;

use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use Onion\Framework\Database\Interfaces\StatementInterface;

class Statement implements StatementInterface
{
    private array $parameters = [];
    private array $types = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $query,
    ) {
    }

    public function bindValue(int|string $name, mixed $value, DataTypes $type = null): void
    {
        $this->parameters[$name] = $value;
        $this->types[$name] = $type ?? DataTypes::TEXT;
    }

    public function bindParam(int|string $name, mixed &$value, DataTypes $type = null): void
    {
        $this->parameters[$name] = &$value;
        $this->types[$name] = $type ?? DataTypes::TEXT;
    }

    public function execute(): ResultSetInterface
    {
        return $this->connection->execute(
            $this->query,
            $this->parameters,
            $this->types,
        );
    }
}
