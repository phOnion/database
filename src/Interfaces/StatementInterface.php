<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Interfaces;

use Onion\Framework\Database\DataTypes;
use Onion\Framework\Database\Interfaces\ResultSetInterface;

interface StatementInterface
{
    public function bindValue(int | string $name, mixed $value, DataTypes $type = null): void;
    public function bindParam(int | string $name, mixed &$value, DataTypes $type = null): void;

    public function execute(): ResultSetInterface;
}
