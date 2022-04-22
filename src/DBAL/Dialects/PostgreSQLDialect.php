<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\Dialects;

use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;

class PostgreSQLDialect implements DialectInterface
{
    public function __construct(private readonly mixed $connection)
    {
    }

    public function escapeValue(mixed $value): string
    {

        return pg_escape_literal($this->connection, $value);
    }

    public function escapeIdentifier(string $value): string
    {
        return pg_escape_identifier($this->connection, $value);
    }

    public function shouldTransform(Node $node): bool
    {
        return $node->kind === TokenKind::IDENTIFIER ||
            $node->kind === TokenKind::STRING;
    }

    public function transform(Node $node): Node
    {
        return match ($node->kind) {
            TokenKind::IDENTIFIER => (new Node($this->escapeIdentifier($node->value), $node->kind, $node->position))->setNext($node->next()),
            TokenKind::STRING => (new Node($this->escapeValue($node->value), $node->kind, $node->position))->setNext($node->next()),
        };
    }
}
