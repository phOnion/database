<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\Operations;

use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;
use Onion\Framework\Database\DBAL\Interfaces\OperationInterface;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;

class InsertOperation implements OperationInterface
{
    private readonly Node $operation;

    private ?Node $into = null;
    private ?Node $columns = null;
    private ?Node $values = null;

    public function __construct(
        private readonly Lexer $lexer,
    ) {
        $this->operation = new Node('INSERT', TokenKind::INSERT);
    }

    private function getExpressionTokens(string | ExpressionInterface | OperationInterface $expr): Node
    {
        if (is_string($expr) || $expr instanceof ExpressionInterface) {
            return $this->lexer->scan((string) $expr);
        }

        return $expr->getOperationToken();
    }

    public function into(string $table): static
    {
        if ($this->into === null) {
            $this->into = new Node('INTO', TokenKind::INTO);
        }

        $this->into->setNext(new Node($table, TokenKind::IDENTIFIER));

        return $this;
    }

    public function columns(string ...$name): static
    {
        $column = new Node(implode(', ', $name), TokenKind::IDENTIFIER);
        if ($this->columns === null) {
            $this->columns = $column;
        } else {
            $this->columns->append(
                (new Node(',', TokenKind::COMMA))->setNext($column)
            );
        }

        return $this;
    }

    public function values(mixed ...$exprs): static
    {

        $values = (new Node('(', TokenKind::OPEN_PARENTHESIS));
        $lastIndex = array_key_last($exprs);
        foreach ($exprs as $idx => $expr) {
            $values->append(
                $this->getExpressionTokens($expr)->setNext(
                    $idx !== $lastIndex ? new Node(',', TokenKind::COMMA) : null
                )
            );
        }
        $values->append(new Node(')', TokenKind::CLOSE_PARENTHESIS));

        if ($this->values === null) {
            $this->values = (new Node('VALUES', TokenKind::VALUES))->setNext($values);
        } else {
            $this->values->append(
                (new Node(',', TokenKind::COMMA))->setNext($values)
            );
        }

        return $this;
    }

    public function getOperationToken(): Node
    {
        $columns = (new Node('(', TokenKind::OPEN_PARENTHESIS))->setNext(
            $this->columns->append(new Node(')', TokenKind::CLOSE_PARENTHESIS))
        );

        return $this->operation
            ->append($this->into)
            ->append($columns)
            ->append($this->values);
    }
}
