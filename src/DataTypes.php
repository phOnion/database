<?php

namespace Onion\Framework\Database;

enum DataTypes
{
    case UUID;
    case BINARY;
    case LOB;
    case BOOLEAN;
    case JSON;
    case TEXT;
    case DATE;
    case TIME;
    case NUMBER;
    case DOUBLE;
    case TIMESTAMP;
    case DATETIME;
}
