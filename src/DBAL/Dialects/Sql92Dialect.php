<?php

namespace Onion\Framework\Database\DBAL\Dialects;

use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;

class Sql92Dialect implements DialectInterface
{
    public function escapeValue(mixed $value): string
    {
        return str_replace("'", "''", $value);
    }

    public function escapeIdentifier(string $value): string
    {
        return "\"$value\"";
    }

    public function shouldTransform(Node $node): bool
    {
        return $node->kind === TokenKind::STRING;
    }

    public function transform(Node $node): Node
    {
        return match ($node->kind) {
            TokenKind::STRING => (new Node(
                "'{$this->escapeValue($node->value)}'",
                $node->kind,
                $node->position
            ))->setNext($node->next()),
            default => $node,
        };
    }
}
