<?php

namespace Onion\Framework\Database\DBAL\Operations;

use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;
use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\DBAL\Interfaces\OperationInterface;
use Onion\Framework\Database\DBAL\Types\ConditionType;
use Onion\Framework\Database\DBAL\Types\JoinDirection;
use Onion\Framework\Database\DBAL\Types\JoinType;
use Onion\Framework\Database\DBAL\Types\Ordering;

class SelectOperation implements OperationInterface
{

    private readonly Node $operation;
    private ?Node $columns = null;
    private ?Node $from = null;
    private ?Node $join = null;
    private ?Node $where = null;
    private ?Node $group = null;
    private ?Node $having = null;
    private ?Node $orderBy = null;
    private ?Node $limit = null;
    private ?Node $offset = null;
    private ?Node $union = null;

    public function __construct(
        private readonly Lexer $lexer,
    ) {
        $this->operation = new Node('SELECT', TokenKind::SELECT);
    }

    private function getExpressionTokens(string | ExpressionInterface | OperationInterface $expr): Node
    {
        if (is_string($expr) || $expr instanceof ExpressionInterface) {
            return $this->lexer->scan((string) $expr);
        }

        return $expr->getOperationToken();
    }

    public function distinct(): static
    {
        $this->operation->setNext(
            new Node('DISTINCT', TokenKind::DISTINCT)
        );

        return $this;
    }

    public function all(): static
    {
        $this->operation->setNext(
            new Node('ALL', TokenKind::ALL)
        );

        return $this;
    }

    public function column(mixed $expr, ?string $alias = null): static
    {
        $column = $this->getExpressionTokens($expr);
        if ($alias !== null) {
            $column->append(
                (new Node('AS', TokenKind::AS))->setNext(new Node($alias, TokenKind::IDENTIFIER))
            );
        }

        if ($this->columns === null) {
            $this->columns = $column;
        } else {
            $this->columns->append(
                (new Node(',', TokenKind::COMMA))->setNext($column)
            );
        }

        return $this;
    }

    public function from(mixed $expr, ?string $alias = null): static
    {
        if ($this->from === null) {
            $this->from = (new Node('FROM', TokenKind::FROM));
        } else {
            $this->from->append(new Node(',', TokenKind::COMMA));
        }

        $identifier = $this->getExpressionTokens($expr);
        if ($alias !== null) {
            $identifier->setNext(
                (new Node('AS', TokenKind::AS))->setNext(new Node($alias, TokenKind::IDENTIFIER))
            );
        }

        $this->from->append($identifier);

        return $this;
    }

    public function join(
        mixed $expr,
        mixed $conditions,
        ?JoinDirection $direction = null,
        JoinType $type = JoinType::INNER,
        ?string $alias = null
    ): static {
        $join = match ($type) {
            JoinType::INNER => new Node('INNER', TokenKind::INNER),
            JoinType::CROSS => new Node('CROSS', TokenKind::CROSS),
            default => match ($direction) {
                JoinDirection::LEFT => (new Node('LEFT', TokenKind::LEFT))->setNext(
                    $type !== null ? new Node('OUTER', TokenKind::OUTER) : null,
                ),
                JoinDirection::RIGHT => (new Node('RIGHT', TokenKind::RIGHT))->setNext(
                    $type !== null ? new Node('RIGHT', TokenKind::RIGHT) : null,
                ),
            },
        };

        $join->append(new Node('JOIN', TokenKind::JOIN));

        if (!$this->join) {
            $this->join = $join;
        } else {
            $this->join->append($join);
        }

        $nodeExpr = $this->getExpressionTokens($expr);
        if ($alias) {
            $nodeExpr->append(
                (new Node('AS', TokenKind::AS))->setNext(new Node($alias, TokenKind::IDENTIFIER))
            );
        }

        $this->join->append(
            $nodeExpr->append(

                (new Node('ON', TokenKind::ON))->setNext((new Node('(', TokenKind::OPEN_PARENTHESIS))->setNext($this->getExpressionTokens($conditions)->append(new Node(')', TokenKind::CLOSE_PARENTHESIS))),
                )
            )
        );

        return $this;
    }

    public function where(mixed $expr, ?ConditionType $condition = null): static
    {
        if ($this->where === null) {
            $this->where = new Node('WHERE', TokenKind::WHERE);
        }

        $identifier = $this->getExpressionTokens($expr);

        $this->where->append(match ($condition) {
            ConditionType::AND => (new Node('AND', TokenKind::AND))
                ->append($identifier),
            ConditionType::OR => (new Node('OR', TokenKind::OR))
                ->append($identifier),
            default => $identifier,
        });

        return $this;
    }

    public function groupBy(mixed $expr): static
    {
        if ($this->group === null) {
            $this->group = (new Node('GROUP', TokenKind::GROUP))->setNext(new Node('BY', TokenKind::BY));
        } else {
            $this->group->append(new Node(',', TokenKind::COMMA));
        }

        $this->group->append($this->getExpressionTokens($expr));

        return $this;
    }

    public function having(mixed $expr): static
    {
        if ($this->having === null) {
            $this->having = new Node('HAVING', TokenKind::HAVING);
        }

        $this->having->append($this->getExpressionTokens($expr));

        return $this;
    }

    public function orderBy(mixed $expr, Ordering $order): static
    {
        if ($this->orderBy === null) {
            $this->orderBy = (new Node('ORDER', TokenKind::ORDER))->setNext(new Node('BY', TokenKind::BY));
        } else {
            $this->orderBy->append(new Node(',', TokenKind::COMMA));
        }

        $this->orderBy->append($this->getExpressionTokens($expr)->setNext(
            match ($order) {
                Ordering::ASCENDING => new Node('ASC', TokenKind::ASC),
                Ordering::DESCENDING => new Node('DESC', TokenKind::DESC),
            }
        ));

        return $this;
    }

    public function limit(mixed $expr): static
    {
        $this->limit = (new Node('LIMIT', TokenKind::LIMIT))->setNext(
            $this->getExpressionTokens($expr)
        );

        return $this;
    }

    public function offset(mixed $expr): static
    {
        $this->offset = (new Node('OFFSET', TokenKind::OFFSET))
            ->setNext($this->getExpressionTokens($expr));

        return $this;
    }

    public function union(mixed $expr): static
    {
        if ($this->union === null) {
            $this->union = new Node('UNION', TokenKind::UNION);
        } else {
            $this->union->append(
                new Node('UNION', TokenKind::UNION),
            );
        }

        $this->union->append(
            (new Node('(', TokenKind::OPEN_PARENTHESIS))
                ->setNext(
                    $this->getExpressionTokens($expr)
                        ->setNext(new Node(')', TokenKind::CLOSE_PARENTHESIS))
                )
        );

        return $this;
    }


    public function getOperationToken(): Node
    {
        $query = (new Node('SELECT', TokenKind::SELECT))
            ->append($this->columns)
            ->append($this->from)
            ->append($this->join)
            ->append($this->where)
            ->append($this->group)
            ->append($this->having)
            ->append($this->orderBy)
            ->append($this->limit)
            ->append($this->offset);

        if ($this->union !== null) {
            $query = (new Node('(', TokenKind::OPEN_PARENTHESIS))->setNext($query)
                ->append(new Node(')', TokenKind::CLOSE_PARENTHESIS));
        }

        return $query->append($this->union);
    }
}
