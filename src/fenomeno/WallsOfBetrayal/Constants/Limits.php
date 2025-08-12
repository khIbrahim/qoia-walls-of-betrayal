<?php

namespace fenomeno\WallsOfBetrayal\Constants;

use pocketmine\utils\Limits as PMLimits;

interface Limits
{
    public const INT63_MAX = PMLimits::INT64_MAX >> 1; // 2^63 - 1, 9,223,372,036,854,775,807 (9 quintillion)
}