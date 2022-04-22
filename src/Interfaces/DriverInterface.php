<?php

namespace Onion\Framework\Database\Interfaces;

use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\Parameter;

interface DriverInterface
{
    public function execute(string $query, Parameter ...$params): ResultSetInterface;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;

    public function resource(): mixed;

    public function clientVersion(): ?string;
    public function serverVersion(): ?string;

    public function getDialect(): DialectInterface;
}
