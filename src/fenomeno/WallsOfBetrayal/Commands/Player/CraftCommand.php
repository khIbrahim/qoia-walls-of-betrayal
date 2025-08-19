<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenu;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\InvMenuTypeIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class CraftCommand extends WCommand
{

    public const PLAYER_ARGUMENT = "player";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = $args[self::PLAYER_ARGUMENT] ?? $sender->getName();
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if ($player === null) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $itSelf = $player->getName() === $sender->getName();
        if (! $itSelf && ! $sender->hasPermission(PermissionIds::CRAFT_OTHER)) {
            MessagesUtils::sendTo($sender, MessagesIds::NO_PERMISSION);
            return;
        }

        try {
            $craftingTable = InvMenu::create(InvMenuTypeIds::TYPE_CRAFT);
            $craftingTable->send($player);
            MessagesUtils::sendTo($sender, MessagesIds::CRAFTING_TABLE_OPENED, [ExtraTags::PLAYER => $player->getName()]);
        } catch (Throwable $e){
            MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            $this->main->getLogger()->error("Error opening crafting table for player {$player->getName()}: " . $e->getMessage());
            $this->main->getLogger()->logException($e);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::CRAFT);
    }
}