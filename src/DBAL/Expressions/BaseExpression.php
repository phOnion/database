<?php

namespace Onion\Framework\Database\DBAL\Expressions;

class BaseExpression implements ExpressionInterface
{
    public function __construct(
        private readonly string $expr
    ) {
    }

    public function __toString(): string
    {
        return $this->expr;
    }
}
