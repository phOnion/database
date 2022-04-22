<?php

namespace Onion\Framework\Database\DBAL\Expressions;

class AggregateExpression extends CompositeExpression
{
    public function __construct(private readonly string $operation, ExpressionInterface ...$expr)
    {
        parent::__construct(...$expr);
    }

    public function __toString(): string
    {
        return '(' . implode(" {$this->operation} ", $this->expr) . ')';
    }
}
