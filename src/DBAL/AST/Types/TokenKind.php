<?php

namespace Onion\Framework\Database\DBAL\AST\Types;


enum TokenKind
{
    case NONE;
    case INTEGER;
    case FLOAT;
    case STRING;
    case PARAMETER;
    case CLOSE_PARENTHESIS;
    case OPEN_PARENTHESIS;
    case COMMA;
    case DIVIDE;
    case DOT;
    case EQUALS;
    case GREATER_THAN;
    case LOWER_THAN;
    case MINUS;
    case MULTIPLY;
    case NEGATE;
    case PLUS;
    case OPEN_CURLY_BRACE;
    case CLOSE_CURLY_BRACE;

    case IDENTIFIER;
    case KEYWORD; // Meta-Keyword to not escape keywords when patching

    case ALL;
    case ALTER;
    case AND;
    case ANY;
    case AS;
    case ASC;
    case BETWEEN;
    case BOTH;
    case BY;
    case CASCADE;
    case CASE;
    case CROSS;
    case DELETE;
    case DESC;
    case DISTINCT;
    case ELSE;
    case EMPTY;
    case END;
    case ESCAPE;
    case EXISTS;
    case FALSE;
    case FROM;
    case FUNCTION;
    case GROUP;
    case HAVING;
    case HIDDEN;
    case ILIKE;
    case IN;
    case INDEX;
    case INNER;
    case INSERT;
    case INSTANCE;
    case INTO;
    case IS;
    case JOIN;
    case LEADING;
    case LEFT;
    case LIKE;
    case LIMIT;
    case MEMBER;
    case NEW;
    case NOT;
    case NULL;
    case OF;
    case OFFSET;
    case ON;
    case OR;
    case ORDER;
    case OUTER;
    case PARTIAL;
    case RIGHT;
    case SELECT;
    case SET;
    case SOME;
    case SUM;
    case TABLE;
    case THEN;
    case TRAILING;
    case TRUE;
    case UNION;
    case UPDATE;
    case USING;
    case VALUES;
    case WHEN;
    case WHERE;
    case WITH;

    public static function fromString(string $kind): ?static
    {
        try {
            return constant(sprintf('%s::%s', static::class, strtoupper($kind)));
        } catch (\Throwable $ex) {
            return null;
        }
    }
}
