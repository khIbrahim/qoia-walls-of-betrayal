<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments\Shop;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class ShopOrCategoryArgument extends StringEnumArgument
{

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[$argument] ?? $argument;
    }

    public function getTypeName(): string
    {
        return "shop_or_category";
    }

    public function getEnumName(): string
    {
        return "shop_or_category";
    }
}