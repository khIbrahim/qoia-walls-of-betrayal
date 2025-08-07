<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\Game\Handlers\KitClaimHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class KitChooseInventory extends WInventory
{

    private const KIT_TAG = 'Kit';

    protected function getInventoryDTO(): object
    {
        $inventoryDTO = InventoriesConfig::getInventoryDTO(InventoriesConfig::CHOOSE_KIT_INVENTORY);
        $inventoryDTO->items = [];
        $kits = array_values(Main::getInstance()->getKitsManager()->getKits());
        foreach ($inventoryDTO->targetIndexes as $i => $index) {
            $kit = $kits[$i] ?? null;
            if ($kit) {
                $item = clone $kit->getItem();
                $item->setCustomName("§r" . $kit->getDisplayName());
                $item->getNamedTag()->setString(self::KIT_TAG, $kit->getId());

                $lore = [];

                $lore[] = "§r" . $kit->getDescription();
                $lore[] = "§r§8Unlock Day: §f" . $kit->getUnlockDay();

                $lore[] = "§r";

                $lore[] = "§4Requirements:";

                foreach ($kit->getRequirements() as $req) {
                    $progress = 0; // à remplacer dynamiquement
                    $required = $req->getAmount();
                    $icon = $req->getType() === "item" ? "§7•" : "§7×";
                    $target = ucfirst($req->getTarget());

                    $lore[] = "§r§8  $icon §f$target §7– §f{$progress}§8/§f$required";
                }

                $lore[] = "§r";

                $isCompleted = true;
                if ($isCompleted) {
                    $lore[] = "§r§a✔ Ready to claim";
                } else {
                    $lore[] = "§r§c✖ Incomplete";
                }

                $item->setLore($lore);
                $inventoryDTO->items[$index] = $item;
            }
        }

        return $inventoryDTO;
    }

    protected function onClick(Player $player, Item $item): bool
    {
        if ($item->getNamedTag()->getTag(KitChooseInventory::KIT_TAG) !== null) {
            $kitId = $item->getNamedTag()->getString(KitChooseInventory::KIT_TAG);
            $kit   = Main::getInstance()->getKitsManager()->getKitById($kitId);
            if(! $kit){
                MessagesUtils::sendTo($player, 'kits.unknown', ['{KIT}' => $kitId]);
                return true;
            }

            KitClaimHandler::claim($player, $kit);
        }

        $player->removeCurrentWindow();
        return true;
    }
}