<?php

declare(strict_types=1);

namespace Onion\Framework\Database\Drivers\MySQL;

use mysqli_result;
use Onion\Framework\Collection\Collection;
use Onion\Framework\Collection\Interfaces\CollectionInterface;
use Onion\Framework\Database\Interfaces\ResultSetInterface;
use Traversable;

use function Onion\Framework\generator;

class ResultSet implements ResultSetInterface
{
    public function __construct(private readonly mysqli_result $result)
    {
    }

    public function items(): CollectionInterface
    {
        return new Collection(generator(function () {
            $this->result->data_seek(0);
            while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
                yield $row;
            }
        }));
    }

    public function count(): int
    {
        return (int) $this->result->num_rows;
    }

    public function getIterator(): Traversable
    {
        return $this->items();
    }

    public function free(): void
    {
        $this->result->free();
    }
}
