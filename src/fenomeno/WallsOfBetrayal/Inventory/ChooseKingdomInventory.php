<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Game\Handlers\JoinKingdomHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\KingdomConfig;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ChooseKingdomInventory extends WInventory
{

    private const KINGDOM_TAG = 'Kingdom';

    protected function getInventoryDTO(): object
    {
        $inventoryDTO = KingdomConfig::getChooseInventoryDTO();
        $inventoryDTO->items = [];
        $kingdoms = array_values(Main::getInstance()->getKingdomManager()->getKingdoms());
        foreach ($inventoryDTO->targetIndexes as $i => $index) {
            $kingdom = $kingdoms[$i] ?? null;
            if ($kingdom) {
                $item = clone $kingdom->item;
                $item->getNamedTag()->setString(ChooseKingdomInventory::KINGDOM_TAG, $kingdom->id);
                $item->setLore([$kingdom->description]);
                $inventoryDTO->items[$index] = $item;
            }
        }

        return $inventoryDTO;
    }

    protected function onClick(Player $player, Item $item): bool
    {
        if ($item->getNamedTag()->getTag(ChooseKingdomInventory::KINGDOM_TAG) !== null) {
            $player->removeCurrentWindow();

            $kingdomId = $item->getNamedTag()->getString(ChooseKingdomInventory::KINGDOM_TAG);
            $kingdom   = Main::getInstance()->getKingdomManager()->getKingdomById($kingdomId);
            if(! $kingdom){
                MessagesUtils::sendTo($player, 'unknownKingdom', ['{KINGDOM}' => $kingdomId]);
                return true;
            }

            JoinKingdomHandler::join($player, $kingdom);
        }
        return true;
    }
}