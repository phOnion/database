<?php

namespace Onion\Framework\Database\DBAL\Types;

enum JoinType
{
    case INNER;
    case CROSS;
    case OUTER;
}
