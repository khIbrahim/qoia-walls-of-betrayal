<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts;

interface BinaryStringParserInterface
{

    public function encode(string $string) : string;

    public function decode(string $string) : string;

}