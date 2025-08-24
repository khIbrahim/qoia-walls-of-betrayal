<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class LobbyCommand extends WCommand
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
        $playerName = (string) ($args[self::PLAYER_ARGUMENT] ?? $sender->getName());
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if(! $player instanceof Player){
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $itSelf = strtolower($sender->getName()) === strtolower($player->getName());
        if(! $itSelf && ! $sender->hasPermission(PermissionIds::LOBBY_OTHER)){
            MessagesUtils::sendTo($sender, MessagesIds::NO_PERMISSION);
            return;
        }

        try {
            $this->main->getServerManager()->getLobbyManager()->teleport($player);
            MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::LOBBY_SUCCESS_SELF : MessagesIds::LOBBY_SUCCESS_OTHER, [ExtraTags::PLAYER => $playerName]);
        } catch (Throwable $e){Utils::onFailure($e, $sender, "Failed to teleport $playerName to the lobby, executed by {$sender->getName()}: " . $e->getMessage());}
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::LOBBY);
    }
}