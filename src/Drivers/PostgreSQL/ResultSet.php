<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\PostgreSQL;

use Onion\Framework\Collection\Collection;
use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use Traversable;

use function Onion\Framework\generator;

class ResultSet implements ResultSetInterface
{

    public function __construct(
        private readonly mixed $resource,
    ) {
    }

    public function items(): CollectionInterface
    {
        return new Collection(generator(function () {
            pg_result_seek($this->resource, 0);
            while ($row = pg_fetch_array($this->resource, null, PGSQL_ASSOC)) {
                yield $row;
            }
        }));
    }

    public function count(): int
    {
        return (pg_affected_rows($this->resource)
            ?: pg_num_rows($this->resource)) ?: 0;
    }

    public function getIterator(): Traversable
    {
        return $this->items();
    }

    public function free(): void
    {
        pg_free_result($this->resource);
    }
}
