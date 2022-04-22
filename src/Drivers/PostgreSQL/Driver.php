<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\PostgreSQL;

use DateTime;
use DateTimeInterface;
use Onion\Framework\Database\Interfaces\DriverInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use Onion\Framework\Database\Drivers\PostgreSQL\ResultSet;
use Onion\Framework\Database\DataTypes;
use RuntimeException;
use InvalidArgumentException;
use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\Dialects\PostgreSQLDialect;
use Onion\Framework\Database\DBAL\Parameter;

use function Onion\Framework\Loop\with;

class Driver implements DriverInterface
{
    private readonly mixed $connection;
    private readonly string $statementCacheAlgo;

    private array $knownStatements = [];

    public function __construct(
        string $hostname = 'localhost',
        int $port = 5432,
        string $username = 'postgres',
        string $password = '',
        string $database = 'postgres',
        string $extra = '',
    ) {
        $dsn = [];
        if ($hostname) {
            $dsn[] = "host={$hostname}";

            if ($port) {
                $dsn[] = "port={$port}";
            }
        }

        if ($username) {
            $dsn[] = "user={$username}";

            if ($password) {
                $dsn[] = "password={$password}";
            }
        }

        if ($database) {
            $dsn[] = "dbname={$database}";
        }

        if ($extra) {
            $dsn[] = "options='{$extra}'";
        }


        $this->statementCacheAlgo = current(array_intersect(['xxh3', 'murmur3f', 'sha256',], hash_algos()));

        $this->connection = pg_pconnect(implode(' ', $dsn), PGSQL_CONNECT_ASYNC | PGSQL_CONNECT_FORCE_NEW);
        if (!$this->connection) {
            throw new RuntimeException('Unable to establish connection to server');
        }
    }

    public function execute(string $query, Parameter ...$parameters): ResultSetInterface
    {
        $statementName = hash($this->statementCacheAlgo, $query);

        if (!isset($this->knownStatements[$statementName])) {
            $anchors = [];
            $preparedQuery = preg_replace_callback(
                '/(\:(?P<name>[a-z_][a-z\d_]+))/i',
                function ($match) use (&$anchors) {
                    $anchors[] = $match['name'];

                    return '$' . count($anchors);
                },
                $query,
            );


            $this->knownStatements[$statementName] = $anchors;
            if (!$this->run(pg_send_prepare(...), $statementName, $preparedQuery)) {
                throw new RuntimeException($this->getLastError());
            }
            $this->getResult();
        }

        $values = [];

        $types = [];
        $params = [];
        foreach ($parameters as $param) {
            $types[$param->name] = $param->type;
            $params[$param->name] = $param->value;
        }

        foreach ($this->knownStatements[$statementName] as $param) {
            $values[] = match ($types[$param] ?? DataTypes::TEXT) {
                DataTypes::LOB => $this->handleLobData($params[$param]),
                DataTypes::BINARY => $this->run(pg_escape_bytea(...), ($params[$param])),
                DataTypes::BOOLEAN => (bool) $params[$param],
                DataTypes::NUMBER => (int) $params[$param],
                DataTypes::TIMESTAMP => $this->handleDate($params[$param], 'U'),
                DataTypes::DATE => $this->handleDate($params[$param], 'Y-m-d'),
                DataTypes::TIME => $this->handleDate($params[$param], 'H:i:sP'),
                DataTypes::DATETIME => $this->handleDate($params[$param], DateTimeInterface::ATOM),
                DataTypes::DOUBLE =>  (float) $params[$param],
                DataTypes::JSON => '"' . $this->run(pg_escape_literal(...), (string) json_encode($params[$param])) . '"',
                DataTypes::UUID => $this->run(pg_escape_string(...), $params[$param]),
                default => $params[$param] !== null ?
                    $this->run(pg_escape_literal(...), (string) $params[$param]) : null,
            };
        }

        if (!$this->run(pg_send_execute(...), $statementName, $values)) {
            throw new RuntimeException($this->getLastError());
        }

        return new ResultSet($this->getResult());
    }

    public function beginTransaction(): void
    {
        $this->run(pg_query(...), 'BEGIN TRANSACTION');
        $this->getResult();
    }

    public function commit(): void
    {
        $this->run(pg_query(...), 'COMMIT');
        $this->getResult();
    }

    public function rollback(): void
    {
        $this->run(pg_query(...), 'ROLLBACK');
        $this->getResult();
    }

    public function clientVersion(): ?string
    {
        return $this->run(pg_version(...))['client'] ?? null;
    }

    public function serverVersion(): ?string
    {
        return $this->run(pg_version(...))['server'] ?? null;
    }

    public function resource(): mixed
    {
        return $this->connection;
    }

    private function getLastError(): string
    {
        return $this->run(pg_last_error(...) ?: '');
    }

    private function getResult()
    {
        $result = $this->run(pg_get_result(...));

        if ($result && !in_array(pg_result_status($result), [PGSQL_COMMAND_OK, PGSQL_TUPLES_OK])) {
            throw new RuntimeException(sprintf(
                "%s: %s",
                pg_result_error_field($result, PGSQL_DIAG_SQLSTATE),
                pg_result_error($result),
            ));
        }

        return $result ?: null;
    }

    private function handleLobData(mixed $value): int
    {
        if (is_integer($value)) {
            return $value;
        }

        $this->beginTransaction();

        $oid = $this->run(pg_lo_create(...), null);
        if (is_resource($value)) {
            fseek($value, 0);
            while (!feof($value)) {
                pg_lo_write($oid, fread($value, 1024));
            }
        } elseif (is_string($value)) {
            pg_lo_write($oid, $value);
        } else {
            throw new InvalidArgumentException(
                'Invalid value provided for binary data, must be one of resource or string'
            );
        }
        pg_lo_close($oid);
        $this->commit();


        return $oid;
    }

    private function run(\Closure $callback, mixed ...$args)
    {
        with(fn () => !pg_connection_busy($this->connection));

        return $callback($this->connection, ...$args);
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

    public function getDialect(): DialectInterface
    {
        return new PostgreSQLDialect($this->connection);
    }
}
