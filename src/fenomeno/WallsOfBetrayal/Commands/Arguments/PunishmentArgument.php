<?php

namespace fenomeno\WallsOfBetrayal\Commands\Arguments;

use fenomeno\WallsOfBetrayal\Class\Punishment\AbstractPunishment;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class PunishmentArgument extends StringEnumArgument
{
    public function __construct(string $name, bool $optional = false)
    {
        foreach ([AbstractPunishment::TYPE_KICK, AbstractPunishment::TYPE_BAN, AbstractPunishment::TYPE_MUTE, AbstractPunishment::TYPE_REPORT] as $type){
            self::$VALUES[strtolower($type)] = strtolower($type);
        }
        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return $this->getValue(strtolower($argument));
    }

    public function getTypeName(): string
    {
        return "punishment";
    }

    public function getEnumName(): string
    {
        return "punishment";
    }
}