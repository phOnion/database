<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\AST;

use Onion\Framework\Database\DBAL\AST\Types\TokenKind;
use WeakReference;

class Node
{
    private ?Node $next = null;
    private ?WeakReference $prev = null;

    public function __construct(
        public readonly string $value,
        public readonly TokenKind $kind,
        public readonly int $position = -1,
    ) {
    }

    public function append(?Node $node): static
    {
        $cursor = $this;
        while ($cursor->next() !== null) {
            $cursor = $cursor->next();
        }

        $cursor->setNext($node);

        return $this;
    }

    public function setNext(?Node $node)
    {
        if ($node !== null) {
            $node->setPrevious($this);
            $this->next = $node;
        }

        return $this;
    }

    private function setPrevious(Node $node)
    {
        $this->prev = WeakReference::create($node);

        return $this;
    }

    public function next(): ?Node
    {
        return $this->next;
    }

    public function prev(): ?Node
    {
        return $this->prev?->get();
    }

    public function __debugInfo()
    {
        return [
            'value' => $this->value,
            'kind' => $this->kind->name,
            'position' => $this->position,
        ];
    }
}
