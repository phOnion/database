<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\MySQL;

use mysqli;
use Onion\Framework\Database\DataTypes;
use Onion\Framework\Database\Interfaces\DriverInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use RuntimeException;

use function Onion\Framework\Loop\with;
use Closure;
use InvalidArgumentException;
use Onion\Framework\Database\DBAL\Dialects\MySQLDialect;
use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\Dialects\Sql92Dialect;
use Onion\Framework\Database\DBAL\Parameter;

class Driver implements DriverInterface
{
    private mysqli $connection;

    public function __construct(
        string $hostname = 'localhost',
        int $port = 3306,
        string $username = 'root',
        string $password = '',
        string $database = 'mysql',
        string $socket = '',
    ) {
        $this->connection = new mysqli($hostname, $username, $password, $database, $port, $socket);
    }

    public function execute(string $query, Parameter ...$params): ResultSetInterface
    {

        $lob = [];
        $typeString = '';
        $parameters = [];
        foreach ($params as $param) {
            $type = 's';
            $value = $param->value;

            switch ($param->type) {
                case DataTypes::UUID:
                case DataTypes::TEXT:
                case DataTypes::DATE:
                case DataTypes::DATE:
                case DataTypes::TIME:
                    $value = $this->run($this->connection->real_escape_string(...), (string) $param->value);
                    break;
                case DataTypes::JSON:
                    $value = $this->run($this->connection->real_escape_string(...), json_encode($param->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS));
                    break;
                case DataTypes::LOB:
                    $value = null;
                    $type = 'b';
                    break;
                case DataTypes::BINARY:
                    $value = (string) $param->value;
                    $type = 'b';
                    break;
                case DataTypes::BOOLEAN:
                    $value = $param->value ? 1 : 0;
                    $type = 'i';
                    break;
                case DataTypes::NUMBER:
                case DataTypes::TIMESTAMP:
                    $value = (int) $param->value;
                    $type = 'i';
                    break;
                case DataTypes::DOUBLE:
                    $value = (float) $param->value;
                    $type = 'f';
                    break;
            }

            $count = 0;
            $query = str_replace(":{$param->name}", '?', $query, count: $count);
            $parameters = [...$parameters, ...array_fill(0, $count, $value)];
            $typeString .= str_repeat($type, $count);
        }


        $stmt = $this->run($this->connection->prepare(...), $query);
        $stmt->bind_param($typeString, ...$parameters);

        foreach ($lob as $position => $value) {
            $this->handleLobData($stmt, $position, $value);
        }

        $this->run($stmt->execute(...));

        $result = $this->run($stmt->get_result(...));
        if (!$result) {
            throw new RuntimeException(
                sprintf('%s: %s',  $this->connection->sqlstate, $stmt->error),
                $stmt->errno,
            );
        }

        return new ResultSet($result);
    }

    public function beginTransaction(): void
    {
        $this->run($this->connection->begin_transaction(...));
    }

    public function commit(): void
    {
        $this->run($this->connection->commit(...));
    }

    public function rollback(): void
    {
        $this->run($this->connection->rollback(...));
    }

    public function resource(): mixed
    {
        return $this->connection;
    }

    public function clientVersion(): ?string
    {
        return $this->parseVersion($this->connection->client_version);
    }

    public function serverVersion(): ?string
    {
        return $this->parseVersion($this->connection->server_version);
    }

    private function parseVersion(int $version): string
    {
        $main = number_format($version / 10000, 0);
        $minor = number_format(($version - ($main * 10000)) / 100);
        $fix = $version - (($main * 10000) + ($minor * 100));

        return "{$main}.{$minor}.{$fix}";
    }

    private function handleLobData(\mysqli_stmt $stmt, int $position, mixed $value): void
    {
        if (is_resource($value)) {
            fseek($value, 0);
            while (!feof($value)) {
                $this->run($stmt->send_long_data(...), $position, fread($value, 1024));
            }
        } elseif (is_string($value)) {
            foreach (str_split($value, 1024) as $chunk) {
                $this->run($stmt->send_long_data(...), $position, $chunk);
            }
        } else {
            throw new InvalidArgumentException(
                'Invalid value provided for binary data, must be one of resource or string'
            );
        }
    }

    private function run(Closure $callback, mixed ...$args)
    {
        with(fn () => $this->connection->get_connection_stats() !== false);

        try {
            return $callback(...$args);
        } catch (\mysqli_sql_exception $ex) {
            throw new RuntimeException(sprintf(
                '%s: %s',
                $this->connection->sqlstate,
                $ex->getMessage(),
            ), $ex->getCode());
        }
    }

    public function getDialect(): DialectInterface
    {
        return new MySQLDialect($this->connection);
    }
}
