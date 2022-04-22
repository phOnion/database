<?php

namespace Onion\Framework\Database\DBAL;

use Onion\Framework\Database\DBAL\Expressions\AggregateExpression;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\DBAL\Expressions\OperatorExpression;

class ExpressionBuilder
{
    private AggregateExpression $expr;

    public function __construct()
    {
        $this->expr = new AggregateExpression('AND');
    }

    public function eq(mixed $left, mixed $right)
    {
        return new OperatorExpression($left, '=', $right);
    }

    public function neq(mixed $left, mixed $right)
    {
        return new OperatorExpression($left, '!=', $right);
    }

    public function lt(mixed $left, mixed $right)
    {
        return new OperatorExpression($left, '<', $right);
    }

    public function gt(mixed $left, mixed $right)
    {
        return new OperatorExpression($left, '>', $right);
    }

    public function between(mixed $left, mixed ...$right)
    {
        return new OperatorExpression($left, 'BETWEEN', implode(' AND ', $right));
    }

    public function notBetween(mixed $left, mixed ...$right)
    {
        return new OperatorExpression($left, 'NOT BETWEEN', implode(' AND ', $right));
    }

    public function and(ExpressionInterface $expr, ExpressionInterface ...$exprs)
    {
        return new AggregateExpression('AND', $expr, ...$exprs);
    }

    public function or(ExpressionInterface $expr, ExpressionInterface ...$exprs)
    {
        return new AggregateExpression('OR', $expr, ...$exprs);
    }

    public function __toString(): string
    {
        return (string) $this->expr;
    }
}
