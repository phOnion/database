<?php

namespace Onion\Framework\Database\Interfaces;

use Onion\Framework\Database\DBAL\ExpressionBuilder;
use Onion\Framework\Database\DBAL\QueryBuilder;

interface ConnectionInterface
{
    public function prepare(string $query): StatementInterface;
    public function execute(string $query, array $params = [], array $types = []): ResultSetInterface;

    public function commit(): void;
    public function rollback(): void;
    public function beginTransaction(): void;

    public function createQueryBuilder(): QueryBuilder;
    public function createExpressionBuilder(): ExpressionBuilder;
}
