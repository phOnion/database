<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\SQLite;

use Onion\Framework\Collection\Collection;
use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use SQLite3Result;
use Traversable;

use function Onion\Framework\generator;

class ResultSet implements ResultSetInterface
{
    private readonly int $count;

    public function __construct(private readonly SQLite3Result $result)
    {
        $result->reset();
        $count = 0;
        while ($result->fetchArray() !== false) {
            $count++;
        }

        $this->count = $count;
    }


    public function items(): CollectionInterface
    {
        return new Collection(generator(function () {
            $this->result->reset();

            while (($row = $this->result->fetchArray(SQLITE3_ASSOC))) {
                yield $row;
            }
        }));
    }

    public function free(): void
    {
        $this->result->finalize();
    }

    public function count(): int
    {
        return $this->count;
    }

    public function getIterator(): Traversable
    {
        return $this->items();
    }
}
