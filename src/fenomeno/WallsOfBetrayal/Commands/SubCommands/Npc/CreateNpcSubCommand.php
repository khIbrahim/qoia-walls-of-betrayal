<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Menus\NpcMenus;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class CreateNpcSubCommand extends WSubCommand
{

    private const ARG_ID = "id";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void{
        $this->registerArgument(0, new RawStringArgument(self::ARG_ID, false));

        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
        assert($sender instanceof Player);
        $id = (string)($args[self::ARG_ID] ?? "");
        NpcMenus::sendCreateMenu($sender, $id);
    }

    public function getCommandDTO(): CommandDTO{
        return CommandsConfig::getCommandById(CommandsIds::NPC_CREATE);
    }
}