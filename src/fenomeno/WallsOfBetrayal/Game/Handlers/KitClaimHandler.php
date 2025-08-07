<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use pocketmine\player\Player;

class KitClaimHandler
{

    public static function claim(Player $player, Kit $kit): void
    {
//        $progress = Main::getInstance()->getPlayerKitProgressManager()
//            ->get($player->getUniqueId()->toString())
//            ->getProgress($kit->getId());
//
//        if (! $progress->isComplete($kit)) {
//            $player->sendMessage("§cYou haven't completed the requirements yet.");
//            return true;
//        }

        foreach ($kit->getArmor() as $index => $armorItem) {
            $player->getArmorInventory()->setItem($index, $armorItem);
        }
        foreach ($kit->getInventory() as $index => $invItem) {
            $player->getInventory()->setItem($index, $invItem);
        }

        $player->sendMessage("§aYou have equipped the §l" . $kit->getDisplayName() . "§r§a kit!");
    }

}