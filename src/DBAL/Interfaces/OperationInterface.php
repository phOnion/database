<?php

namespace Onion\Framework\Database\DBAL\Interfaces;

use Onion\Framework\Database\DBAL\AST\Node;

interface OperationInterface
{
    public function getOperationToken(): Node;
}
