<?php

namespace Onion\Framework\Database\DBAL\Expressions;

class CompositeExpression implements ExpressionInterface
{
    protected array $expr;

    public function __construct(ExpressionInterface ...$expr)
    {
        $this->expr = $expr;
    }

    public function push(ExpressionInterface $expr)
    {
        $this->expr[] = $expr;
    }

    public function __toString(): string
    {
        return '(' . array_map(fn (ExpressionInterface $expr) => (string) $expr, $this->expr) . ')';
    }
}
