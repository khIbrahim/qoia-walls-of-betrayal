<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc;

use fenomeno\WallsOfBetrayal\Commands\Arguments\NpcArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

final class TpNpcSubCommand extends WSubCommand {

    private const ARG_ID = "id";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void{
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new NpcArgument(self::ARG_ID));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
        $id = (string)$args[self::ARG_ID];
        $npc = $this->main->getNpcManager()->getNpcById($id);
        if(! $npc instanceof NpcEntity){
            MessagesUtils::sendTo($sender, MessagesIds::NPC_NOT_FOUND, [ExtraTags::NPC => $id]);
            return;
        }
        $sender->teleport($npc->getLocation());
        MessagesUtils::sendTo($sender, MessagesIds::TELEPORTED_TO_NPC, [ExtraTags::NPC => $id]);
    }

    public function getCommandDTO(): CommandDTO{
        return CommandsConfig::getCommandById(CommandsIds::NPC_TP);
    }
}