<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Game\Handlers\KitClaimHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class KitChooseInventory extends WInventory
{

    private const KIT_TAG = 'Kit';

    public function __construct(protected readonly Player $player)
    {
        parent::__construct();
    }

    protected function getInventoryDTO(): InventoryDTO
    {
        $session      = Session::get($this->player);
        $inventoryDTO = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::CHOOSE_KIT_INVENTORY);
        $inventoryDTO->items = [];
        $kits = array_values(Main::getInstance()->getKitsManager()->getKitsByKingdom($session->isLoaded() ? $session->getKingdom() : null));
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
                    $progress = $req->getProgress();
                    $required = $req->getAmount();
                    $icon = $req->getType() === KitRequirementType::BREAK ? "§7•" : "§7×";
                    $target = ucfirst((string) $req->getTarget());

                    $lore[] = "§r§8  $icon §f$target §7– §f{$progress}§8/§f$required";
                }

                $lore[] = "§r";

                $unlocked = $kit->isUnlocked();
                if ($unlocked) {
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

    protected function onClickLegacy(Player $player, Item $item): bool
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