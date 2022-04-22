<?php

declare(strict_types=1);

namespace Onion\Framework\Database;

use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\DBAL\QueryBuilder;
use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\Interfaces\DriverInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use Onion\Framework\Database\Interfaces\StatementInterface;
use Onion\Framework\Database\DBAL\ExpressionBuilder;
use Onion\Framework\Database\DBAL\Parameter;

class Connection implements ConnectionInterface
{
    public function __construct(
        private readonly DriverInterface $driver,
        private readonly Lexer $lexer = new Lexer()
    ) {
    }

    public function prepare(string $query): StatementInterface
    {
        return new Statement($this, $query);
    }

    public function execute(string $query, array $params = [], array $types = []): ResultSetInterface
    {
        foreach ($params as $name => $value) {
            if ($value instanceof Parameter) {
                continue;
            }

            $params[$name] = new Parameter($name, $value, $types[$name] ?? DataTypes::TEXT);
        }

        return $this->driver->execute($query, ...$params);
    }

    public function beginTransaction(): void
    {
        $this->driver->beginTransaction();
    }

    public function commit(): void
    {
        $this->driver->commit();
    }

    public function rollback(): void
    {
        $this->driver->rollback();
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->lexer, $this->driver->getDialect());
    }

    public function createExpressionBuilder(): ExpressionBuilder
    {
        return new ExpressionBuilder();
    }
}
