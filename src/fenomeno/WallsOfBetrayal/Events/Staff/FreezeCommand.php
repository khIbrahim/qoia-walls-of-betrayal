<?php

namespace fenomeno\WallsOfBetrayal\Events\Staff;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class FreezeCommand extends WCommand
{

    private const PLAYER_ARGUMENT = "player";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args[self::PLAYER_ARGUMENT] ?? "";
        $target = $this->main->getServer()->getPlayerExact($targetName);

        if(! $target instanceof Player){
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $targetName]);
            return;
        }

        $session = Session::get($target);
        $newState = ! $session->isFrozen();
        $session->setFrozen($newState);

        MessagesUtils::sendTo($sender, $newState ? MessagesIds::FREEZE_ENABLED_ON_PLAYER : MessagesIds::FREEZE_DISABLED_ON_PLAYER, [
            ExtraTags::PLAYER => $target->getName()
        ]);

    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::FREEZE);
    }
}