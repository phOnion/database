<?php

namespace Onion\Framework\Database\DBAL\Dialects;

use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;

class MySQLDialect extends Sql92Dialect
{
    public function __construct(private readonly \mysqli $connection)
    {
    }

    public function escapeValue(mixed $value): string
    {
        return parent::escapeValue($this->connection->escape_string($value));
    }

    public function shouldTransform(Node $node): bool
    {
        return parent::shouldTransform($node) ||
            $node->kind === TokenKind::ILIKE ||
            $node->kind === TokenKind::LIKE ||
            $node->kind === TokenKind::OR ||
            $node->kind === TokenKind::AND ||
            $node->kind === TokenKind::WHERE;
    }

    public function transform(Node $node): Node
    {
        // Force MYSQL to do case-sensitive comparisons, like PG for case insensitive comparisons it will be best to use ILIKE
        if (
            $node->kind === TokenKind::LIKE ||
            $node->kind === TokenKind::OR ||
            $node->kind === TokenKind::AND ||
            $node->kind === TokenKind::WHERE
        ) {
            $next = $node->next();
            $node->setNext((new Node('BINARY', TokenKind::KEYWORD))->setNext($next));
        }

        return match ($node->kind) {
            TokenKind::ILIKE => (new Node('LIKE', TokenKind::LIKE, $node->position))->setNext($node->next()),
            default => parent::transform($node),
        };
    }
}
