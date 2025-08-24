<?php

namespace fenomeno\WallsOfBetrayal\Commands\Admin;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\CleanupNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\CreateNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\DeleteNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\EditNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\ListNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\LoadNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\MoveNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc\TpNpcSubCommand;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;

class NpcCommand extends WCommand
{

    private const ID_ARGUMENT = 'name';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::ID_ARGUMENT, false));

        $this->registerSubCommand(new DeleteNpcSubCommand($this->main));
        $this->registerSubCommand(new CreateNpcSubCommand($this->main));
        $this->registerSubCommand(new EditNpcSubCommand($this->main));
        $this->registerSubCommand(new MoveNpcSubCommand($this->main));
        $this->registerSubCommand(new TpNpcSubCommand($this->main));
        $this->registerSubCommand(new ListNpcSubCommand($this->main));
        $this->registerSubCommand(new LoadNpcSubCommand($this->main));
        $this->registerSubCommand(new CleanupNpcSubCommand($this->main));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsage());
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NPC);
    }
}