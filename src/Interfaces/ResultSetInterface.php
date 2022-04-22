<?php

namespace Onion\Framework\Database\Interfaces;

use Countable;
use IteratorAggregate;
use Onion\Framework\Collection\Interfaces\CollectionInterface;

interface ResultSetInterface extends Countable, IteratorAggregate
{
    public function items(): CollectionInterface;
    public function free(): void;
}
