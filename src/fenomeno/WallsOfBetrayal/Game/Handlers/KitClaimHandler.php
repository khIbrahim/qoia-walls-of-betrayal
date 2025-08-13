<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;

final class KitClaimHandler
{

    public static function claim(Player $player, Kit $kit): void
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            $player->kick(MessagesUtils::getMessage('common.unstable'));
            return;
        }

        if(! $player->hasPermission($kit->getPermission())){
            MessagesUtils::sendTo($player, 'kits.noPermission', ['{KIT}' => $kit->getDisplayName()]);
            return;
        }

        if($kit->hasKingdom() && $session->getKingdom() !== null && $kit->getKingdom()->getId() !== $session->getKingdom()->getId()){
            MessagesUtils::sendTo($player, 'kits.notSameKingdom', ['{KINGDOM}' => $kit->getKingdom()->getDisplayName()]);
            return;
        }

        if ($kit->hasRequirements() && ! $kit->isRequirementsAchieved()) {
            MessagesUtils::sendTo($player, 'kits.requirementsNotAchieved');
            return;
        }

        $kitId = $kit->getId();
        $playerName = $player->getName();
        $cooldownManager = Main::getInstance()->getCooldownManager();

        if ($cooldownManager->isOnCooldown($kitId, $playerName)) {
            MessagesUtils::sendTo($player, 'kits.cooldown', [
                '{KIT}' => $kit->getDisplayName(),
                '{TIME}' => Utils::formatDuration($cooldownManager->getCooldownRemaining($kitId, $playerName))
            ]);
            return;
        }

        Utils::giveItemSet($player, $kit->getInventory());
        Utils::giveItemSet($player, $kit->getArmor(), true);

        Main::getInstance()->getCooldownManager()->setCooldown($kit->getId(), $player->getName(), $kit->getCooldown());
        MessagesUtils::sendTo($player, 'kits.claimed', ['{KIT}' => $kit->getId()]);
    }
}