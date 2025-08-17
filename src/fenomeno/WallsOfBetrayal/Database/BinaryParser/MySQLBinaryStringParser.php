<?php

namespace fenomeno\WallsOfBetrayal\Database\BinaryParser;

use fenomeno\WallsOfBetrayal\Database\Contrasts\BinaryStringParserInterface;

class MySQLBinaryStringParser implements BinaryStringParserInterface
{

    public function encode(string $string) : string{
        return $string;
    }

    public function decode(string $string) : string{
        return $string;
    }
}