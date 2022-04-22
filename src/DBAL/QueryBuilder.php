<?php

namespace Onion\Framework\Database\DBAL;

use Onion\Framework\Database\DBAL\Interfaces\DialectInterface;
use Onion\Framework\Database\DBAL\AST\Lexer;
use Onion\Framework\Database\DBAL\Operations\SelectOperation;
use Onion\Framework\Database\DBAL\AST\Node;
use Onion\Framework\Database\DBAL\Interfaces\OperationInterface;
use Onion\Framework\Database\DBAL\Operations\DeleteOperation;
use Onion\Framework\Database\DBAL\Operations\InsertOperation;
use Onion\Framework\Database\DBAL\Operations\UpdateOperation;

class QueryBuilder
{
    public function __construct(
        private readonly Lexer $lexer,
        private readonly DialectInterface $dialect
    ) {
    }

    public function select(): SelectOperation
    {
        return new SelectOperation($this->lexer);
    }

    public function insert(): InsertOperation
    {
        return new InsertOperation($this->lexer);
    }

    public function delete(): DeleteOperation
    {
        return new DeleteOperation($this->lexer);
    }

    public function update(): UpdateOperation
    {
        return new UpdateOperation($this->lexer);
    }

    public function getSql(
        OperationInterface $operation
    ): string {
        return $this->transformNode($operation->getOperationToken());
    }

    private function transformNode(?Node $node)
    {
        if ($node === null) {
            return '';
        }

        if ($this->dialect->shouldTransform($node)) {
            $node = $this->dialect->transform($node);
        }

        return ($node->value) . ' ' . $this->transformNode($node->next());
    }
}
