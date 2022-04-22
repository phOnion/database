<?php

declare(strict_types=1);

namespace Onion\Framework\Database\DBAL\AST;

use Onion\Framework\Database\DBAL\AST\Interfaces\TypeResolverInterface;
use Onion\Framework\Database\DBAL\AST\Node;

use Onion\Framework\Database\DBAL\AST\BaseTypeResolver;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;

class Lexer
{
    private readonly string $regex;
    private readonly TypeResolverInterface $typeResolver;

    public function __construct(
        ?TypeResolverInterface $typeResolver = null
    ) {
        $this->typeResolver = $typeResolver ?? new BaseTypeResolver();

        $this->regex = sprintf(
            '/(%s)|%s/%s',
            implode(')|(', [
                '[a-z_][a-z0-9]*\(', // function calls avoid knowing them all
                '[a-z_][a-z0-9_]*', // aliased name, identifier or qualified name
                '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?', // numbers
                "'(?:[^']|'')*'", // single-quoted strings
                '"(?:[^"]|"")*"', // double-quoted strings
                '\?[0-9]*|:[a-z_][a-z0-9_]*', // parameters
            ]),
            implode('|', ['\s+', '--.*', '(.)']),
            'iu'
        );
    }

    public function scan(string $query): Node
    {
        /** @var array|false $matches */
        $matches = preg_split(
            $this->regex,
            $query,
            flags: PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE,
        );

        return $this->getNode(0, $matches);
    }

    private function getNode($idx, &$matches): ?Node
    {

        if (!isset($matches[$idx])) {
            return null;
        }

        $match = &$matches[$idx];
        $type = $this->typeResolver->getType($match[0]);

        $node = new Node($match[0], $type, $match[1]);

        // Separate function calls into separate tokens
        if ($type === TokenKind::FUNCTION) {
            $node = (new Node(
                substr($node->value, 0, -1),
                TokenKind::KEYWORD,
                $node->position
            ))->setNext(new Node('(', TokenKind::OPEN_PARENTHESIS));
        }

        return $node->append(
            $this->getNode($idx + 1, $matches)
        );
    }
}
