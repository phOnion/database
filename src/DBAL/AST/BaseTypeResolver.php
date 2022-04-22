<?php

namespace Onion\Framework\Database\DBAL\AST;

use Onion\Framework\Database\DBAL\AST\Interfaces\TypeResolverInterface;
use Onion\Framework\Database\DBAL\AST\Types\TokenKind;

class BaseTypeResolver implements TypeResolverInterface
{
    public function getType(string &$value): TokenKind
    {
        return match (true) {
            $value[-1] === '(
            ' => TokenKind::FUNCTION,
            $value[0] === ':' => TokenKind::PARAMETER,
            $value[0] === '(' => TokenKind::OPEN_PARENTHESIS,
            $value[0] === ')' => TokenKind::CLOSE_PARENTHESIS,
            $value[0] === ',' => TokenKind::COMMA,
            $value[0] === '/' => TokenKind::DIVIDE,
            $value[0] === '.' => TokenKind::DOT,
            $value[0] === '=' => TokenKind::EQUALS,
            $value[0] === '>' => TokenKind::GREATER_THAN,
            $value[0] === '<' => TokenKind::LOWER_THAN,
            $value[0] === '-' => TokenKind::MINUS,
            $value[0] === '*' => TokenKind::MULTIPLY,
            $value[0] === '!' => TokenKind::NEGATE,
            $value[0] === '+' => TokenKind::PLUS,
            $value[0] === '{' => TokenKind::OPEN_CURLY_BRACE,
            $value[0] === '}' => TokenKind::CLOSE_CURLY_BRACE,
            $value[0] === "'" => $this->handleStringValues("'", $value),
            $value[0] === '"' => $this->handleStringValues('"', $value),
            is_numeric($value) =>
            strpos($value, '.') !== false || stripos($value, 'e') !== false ?
                TokenKind::FLOAT : TokenKind::INTEGER,
            ctype_alpha($value[0]) => TokenKind::fromString($value) ?? TokenKind::IDENTIFIER,
            default => TokenKind::NONE,
        };
    }

    private function handleStringValues(string $separator, string &$value): TokenKind
    {
        $value = str_replace($separator . $separator, $separator, substr($value, 1, strlen($value) - 2));

        return TokenKind::STRING;
    }
}
