<?php

declare(strict_types=1);

namespace Onion\Framework\Database;

use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\Drivers\MySQL\Driver as MySQLDriver;
use Onion\Framework\Database\Drivers\PostgreSQL\Driver as PostgreSQLDriver;
use Onion\Framework\Database\Drivers\SQLite\Driver as SQLiteDriver;
use Onion\Framework\Database\Interfaces\ConnectionInterface;
use Onion\Framework\Database\Interfaces\DriverInterface;
use Onion\Framework\Database\ORM\EntityManager;
use Onion\Framework\Database\ORM\Interfaces\EntityManagerInterface;
use Onion\Framework\Database\ORM\Interfaces\RepositoryInterface;
use Onion\Framework\Database\ORM\Interfaces\UnitOfWorkInterface;
use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use Onion\Framework\Proxy\Interfaces\ProxyFactoryInterface;
use Psr\SimpleCache\CacheInterface;

class DatabaseServiceProvider implements ServiceProviderInterface
{

    public function register(ContainerInterface $provider): void
    {
        $provider->singleton(MySQLDriver::class, fn (ContainerInterface $c) => new MySQLDriver(...$c->get('database.mysql')));
        $provider->singleton(PostgreSQLDriver::class, fn (ContainerInterface $c) => new PostgreSQLDriver(...$c->get('database.pgsql')));
        $provider->singleton(SQLiteDriver::class, fn (ContainerInterface $c) => new SQLiteDriver(...$c->get('database.sqlite')));

        $provider->singleton(Connection::class, fn (ContainerInterface $c) => new Connection(
            $c->get(DriverInterface::class),
            $c->get(Lexer::class),
        ));

        $provider->singleton(EntityManager::class, fn (ContainerInterface $c) => new EntityManager(
            $c->get(ConnectionInterface::class),
            $c->has(UnitOfWorkInterface::class) ? $c->get(UnitOfWorkInterface::class) : null,
            $c->has(ProxyFactoryInterface::class) ? $c->get(ProxyFactoryInterface::class) : null,
            $c->has(CacheInterface::class) ? $c->get(CacheInterface::class) : null,
        ));

        $provider->bind(RepositoryInterface::class, fn (ContainerInterface $c, string $key) => $c->get(EntityManagerInterface::class)->getRepository($key));
    }
}
