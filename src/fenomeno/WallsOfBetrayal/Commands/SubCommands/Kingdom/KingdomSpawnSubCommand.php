<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;

class KingdomSpawnSubCommand extends WSubCommand
{

    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->getServer()->dispatchCommand($sender, isset($args[self::PLAYER_ARGUMENT]) ? "spawn " . ((string) $args[self::PLAYER_ARGUMENT]) : "spawn");
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_SPAWN);
    }
}