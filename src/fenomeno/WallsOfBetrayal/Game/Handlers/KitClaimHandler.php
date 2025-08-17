<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;

final class KitClaimHandler
{

    public static function claim(Player $player, Kit $kit, bool $force = false): void
    {
        $session = Session::get($player);
        if(! $force && ! $session->isLoaded()){
            $player->kick(MessagesUtils::getMessage('common.unstable'));
            return;
        }

        if(! $force && ! $player->hasPermission($kit->getPermission())){
            MessagesUtils::sendTo($player, 'kits.noPermission', [ExtraTags::KIT => $kit->getDisplayName()]);
            return;
        }

        if(! $force && ($kit->hasKingdom() && $session->getKingdom() !== null && $kit->getKingdom()->getId() !== $session->getKingdom()->getId())){
            MessagesUtils::sendTo($player, 'kits.notSameKingdom', [ExtraTags::KINGDOM => $kit->getKingdom()->getDisplayName()]);
            return;
        }

        if (! $force && ($kit->hasRequirements() && ! $kit->isRequirementsAchieved())) {
            MessagesUtils::sendTo($player, 'kits.requirementsNotAchieved');
            return;
        }

        $kitId = $kit->getId();
        $playerName = $player->getName();
        $cooldownManager = Main::getInstance()->getCooldownManager();

        if (! $force && $cooldownManager->isOnCooldown($kitId, $playerName)) {
            MessagesUtils::sendTo($player, 'kits.cooldown', [
                ExtraTags::KIT  => $kit->getDisplayName(),
                ExtraTags::TIME => Utils::formatDuration($cooldownManager->getCooldownRemaining($kitId, $playerName))
            ]);
            return;
        }

        Utils::giveItemSet($player, $kit->getInventory());
        Utils::giveItemSet($player, $kit->getArmor(), true);

        if (! $force){
            Main::getInstance()->getCooldownManager()->setCooldown($kit->getId(), $player->getName(), $kit->getCooldown());
        }
        MessagesUtils::sendTo($player, 'kits.claimed', [ExtraTags::KIT => $kit->getId()]);
    }
}