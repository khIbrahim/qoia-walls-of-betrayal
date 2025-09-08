<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class KingdomVoteTypeArgument extends StringEnumArgument
{

    public function __construct(string $name, bool $optional = false)
    {
        foreach (KingdomVoteType::cases() as $type) {
            self::$VALUES[$type->value] = $type->value;
        }
        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender): ?KingdomVoteType
    {
        return KingdomVoteType::tryFrom($argument) ?? null;
    }

    public function getTypeName(): string
    {
        return "kingdom-vote-type";
    }

    public function getEnumName(): string
    {
        return "kingdom-vote-type";
    }
}