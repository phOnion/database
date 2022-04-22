<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\Interfaces;

use Onion\Framework\Database\DBAL\AST\Node;

interface DialectInterface
{
    public function escapeValue(mixed $value): string;
    public function escapeIdentifier(string $value): string;
    public function shouldTransform(Node $node): bool;
    public function transform(Node $node): Node;
}
