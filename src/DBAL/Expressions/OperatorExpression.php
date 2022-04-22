<?php

namespace Onion\Framework\Database\DBAL\Expressions;

class OperatorExpression extends BaseExpression
{
    public function __construct(
        public readonly mixed $left,
        public readonly string $operator,
        public readonly mixed $right
    ) {
        parent::__construct("{$left} {$operator} {$right}");
    }
}
