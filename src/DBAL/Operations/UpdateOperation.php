<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\Operations;

use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;
use Onion\Framework\Database\DBAL\Expressions\ExpressionInterface;
use Onion\Framework\Database\DBAL\Interfaces\OperationInterface;
use Onion\Framework\Database\DBAL\Types\ConditionType;

class UpdateOperation implements OperationInterface
{
    private readonly Node $operation;
    private ?Node $table = null;
    private ?Node $set = null;
    private ?Node $where = null;

    public function __construct(
        private readonly Lexer $lexer
    ) {
        $this->operation = new Node('UPDATE', TokenKind::UPDATE);
    }

    private function getExpressionTokens(string | ExpressionInterface | OperationInterface $expr): Node
    {
        if (is_string($expr) || $expr instanceof ExpressionInterface) {
            return $this->lexer->scan((string) $expr);
        }

        return $expr->getOperationToken();
    }

    public function table(mixed $expr, ?string $alias = null): static
    {
        $identifier = $this->getExpressionTokens($expr);
        if ($alias !== null) {
            $identifier->setNext(
                (new Node('AS', TokenKind::AS))->setNext(new Node($alias, TokenKind::IDENTIFIER))
            );
        }

        $this->table = $identifier;

        return $this;
    }

    public function set(mixed $expr): static
    {
        if ($this->set === null) {
            $this->set = new Node('SET', TokenKind::SET);
        } else {
            $this->set->append(new Node(',', TokenKind::COMMA));
        }

        $this->set->append($this->getExpressionTokens($expr));

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

    public function getOperationToken(): Node
    {
        return $this->operation
            ->append($this->table)
            ->append($this->set)
            ->append($this->where);
    }
}
