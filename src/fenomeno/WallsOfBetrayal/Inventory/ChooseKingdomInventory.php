<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\JoinKingdomHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class ChooseKingdomInventory extends WInventory
{

    private const KINGDOM_TAG = 'Kingdom';

    protected function getInventoryDTO(): InventoryDTO
    {
        $inventoryDTO = InventoriesConfig::getInventoryDTO(InventoriesConfig::CHOOSE_KINGDOM_INVENTORY);
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

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        if ($item->getNamedTag()->getTag(ChooseKingdomInventory::KINGDOM_TAG) !== null) {

            $kingdomId = $item->getNamedTag()->getString(ChooseKingdomInventory::KINGDOM_TAG);
            $kingdom   = Main::getInstance()->getKingdomManager()->getKingdomById($kingdomId);
            if(! $kingdom){
                MessagesUtils::sendTo($player, 'unknownKingdom', ['{KINGDOM}' => $kingdomId]);
                return true;
            }

            JoinKingdomHandler::join($player, $kingdom);
        }

        $player->removeCurrentWindow();
        return true;
    }

    protected function onClose(Player $player, Inventory $inventory): void
    {
        $session = Session::get($player);
        if($session->getKingdom() !== null || $session->isChoosingKingdom()){
            return;
        }
        $player->setNoClientPredictions();
        MessagesUtils::sendTo($player, 'cantCloseChooseInventory');
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            soundName: 'mob.villager.no',
            x: $player->getPosition()->x,
            y: $player->getPosition()->y,
            z: $player->getPosition()->z,
            volume: 1.0,
            pitch: 1.0
        ));

        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
            if(! $player->isConnected()){
                return;
            }

            $player->setNoClientPredictions(false);
            (new ChooseKingdomInventory())->send($player);
        }), 20 * 2);
    }
}