<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Enum\KingdomVoteStatus;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class KingdomVoteStatusArgument extends StringEnumArgument
{

    public function __construct(string $name, bool $optional = false)
    {
        foreach (KingdomVoteStatus::cases() as $status) {
            self::$VALUES[$status->value] = $status->value;
        }

        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender): ?KingdomVoteStatus
    {
        return KingdomVoteStatus::tryFrom(self::$VALUES[$argument]) ?? null;
    }

    public function getTypeName(): string
    {
        return "kingdom_vote_status";
    }

    public function getEnumName(): string
    {
        return "kingdom_vote_status";
    }
}