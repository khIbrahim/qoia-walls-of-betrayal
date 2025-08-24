<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;
use Throwable;

class SpawnCommand extends WCommand
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
        if(! $itSelf && ! $sender->hasPermission(PermissionIds::SPAWN_OTHER)){
            MessagesUtils::sendTo($sender, MessagesIds::NO_PERMISSION);
            return;
        }

        $session = Session::get($player);
        if(! $session->isLoaded()){
            MessagesUtils::sendTo($sender, MessagesIds::SESSION_NOT_LOADED, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $kingdom = $session->getKingdom();
        if($kingdom === null){
            MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::SPAWN_NO_KINGDOM : MessagesIds::SPAWN_NO_KINGDOM_OTHER, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $spawn = $kingdom->getSpawn();
        if ($spawn === null){
            MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::SPAWN_NO_KINGDOM_SPAWN : MessagesIds::SPAWN_NO_KINGDOM_SPAWN_OTHER, [
                ExtraTags::PLAYER  => $playerName,
                ExtraTags::KINGDOM => $kingdom->getDisplayName()
            ]);
            return;
        }

        try {
            $player->teleport($spawn);
            $player->broadcastSound(new EndermanTeleportSound());
            $player->getWorld()->addParticle($spawn, new EndermanTeleportParticle());
            MessagesUtils::sendTo($sender, $itSelf ? MessagesIds::SPAWN_SUCCESS_SELF : MessagesIds::SPAWN_SUCCESS_OTHER, [
                ExtraTags::PLAYER  => $playerName,
                ExtraTags::KINGDOM => $kingdom->getDisplayName()
            ]);
        } catch (Throwable $e){Utils::onFailure($e, $sender, "Failed to teleport $playerName to their kingdom spawn, executed by {$sender->getName()}: " . $e->getMessage());}
    }
    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SPAWN);
    }
}