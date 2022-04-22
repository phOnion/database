<?php

namespace Onion\Framework\Database\DBAL\AST\Interfaces;

interface TypeResolverInterface
{
    public function getType(string &$value): mixed;
}
