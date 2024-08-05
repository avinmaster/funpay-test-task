<?php

namespace FpDbTest\src\Enums;

enum ArgumentSpecifier: string
{
    case MIXED = '?';
    case INTEGER = 'd';
    case FLOAT = 'f';
    case ARRAY = 'a';
    case IDENTIFIER = '#';

    public static function getSpecifiers(): array
    {
        return [
            self::MIXED->value,
            self::INTEGER->value,
            self::FLOAT->value,
            self::ARRAY->value,
            self::IDENTIFIER->value,
        ];
    }
}
