<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\SQLite;

use Closure;
use DateTime;
use DateTimeInterface;
use Onion\Framework\Database\DataTypes;
use Onion\Framework\Database\DBAL\Dialects\Sql92Dialect;
use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\Parameter;
use Onion\Framework\Database\Interfaces\DriverInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use RuntimeException;
use SQLite3;

class Driver implements DriverInterface
{

    private SQLite3 $connection;

    public function __construct(
        string $filename,
        int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        string $encryptionKey = '',
    ) {
        $this->connection = new SQLite3($filename, $flags, $encryptionKey);
        $this->connection->enableExceptions(false);
    }

    public function execute(string $query, Parameter ...$params): ResultSetInterface
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->run($this->connection->prepare(...), $query);
        if (!$stmt) {
            throw new RuntimeException(sprintf('%s: %s', $this->connection->lastErrorCode(), $this->connection->lastErrorMsg()));
        }

        foreach ($params as $param) {
            $stmt->bindParam($param->name, match ($param->type) {
                DataTypes::BINARY => $param->value,
                DataTypes::BOOLEAN => (int) $param->value,
                DataTypes::NUMBER => (int) $param->value,
                DataTypes::TIMESTAMP => $this->handleDate($param->value, 'U'),
                DataTypes::DATE => $this->handleDate($param->value, 'Y-m-d'),
                DataTypes::TIME => $this->handleDate($param->value, 'H:i:sP'),
                DataTypes::DATETIME => $this->handleDate($param->value, DateTimeInterface::ATOM),
                DataTypes::DOUBLE => (float) $param->value,
                DataTypes::JSON => $this->connection->escapeString(json_encode($param->value)),
                DataTypes::UUID => $this->connection->escapeString($param->value),
                DataTypes::TEXT => $this->connection->escapeString($param->value),
            }, match ($param->type) {
                DataTypes::BINARY => SQLITE3_TEXT,
                DataTypes::BOOLEAN => SQLITE3_INTEGER,
                DataTypes::NUMBER => SQLITE3_INTEGER,
                DataTypes::TIMESTAMP => SQLITE3_INTEGER,
                DataTypes::DATE => SQLITE3_TEXT,
                DataTypes::TIME => SQLITE3_TEXT,
                DataTypes::DATETIME => SQLITE3_TEXT,
                DataTypes::DOUBLE => SQLITE3_FLOAT,
                DataTypes::JSON => SQLITE3_TEXT,
                DataTypes::UUID => SQLITE3_TEXT,
                DataTypes::TEXT => SQLITE3_TEXT,
            });
        }


        $result = $stmt->execute();
        if (!$result) {
            throw new RuntimeException(
                sprintf('%s: %s', $this->connection->lastErrorCode(), $this->connection->lastErrorMsg()),

            );
        }

        return new ResultSet($result);
    }

    public function beginTransaction(): void
    {
        $this->connection->exec('BEGIN TRANSACTION');
    }

    public function commit(): void
    {
        $this->connection->exec('COMMIT');
    }

    public function rollback(): void
    {
        $this->connection->exec('ROLLBACK');
    }

    public function resource(): mixed
    {
        return $this->connection;
    }

    public function clientVersion(): ?string
    {
        return $this->connection->version()['versionString'] ?? null;
    }

    public function serverVersion(): ?string
    {
        return $this->connection->version()['versionString'] ?? null;
    }

    public function getDialect(): DialectInterface
    {
        return new Sql92Dialect();
    }

    private function handleDate(mixed $value, string $format): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        } else if (is_int($value)) {
            return date($format, $value);
        } else {
            return (new DateTime($value))->format($format);
        }
    }

    private function run(Closure $closure, mixed ...$args): mixed
    {
        return @$closure(...$args);
    }
}
