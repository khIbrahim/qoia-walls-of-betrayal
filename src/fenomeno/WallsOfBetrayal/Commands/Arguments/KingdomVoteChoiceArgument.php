<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class KingdomVoteChoiceArgument extends StringEnumArgument
{

    public static array $VALUES = [
        'yes' => 'yes',
        'no' => 'no',
    ];

    public function parse(string $argument, CommandSender $sender): mixed
    {
        return self::$VALUES[$argument] ?? null;
    }

    public function getTypeName(): string
    {
        return 'kingdom_vote_choice';
    }

    public function getEnumName(): string
    {
        return 'kingdom_vote_choice';
    }
}