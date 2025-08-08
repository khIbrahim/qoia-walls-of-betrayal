<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenu;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\transaction\InvMenuTransaction;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class WInventory
{

    protected InvMenu $invMenu;

    public function __construct() {
        $dto = $this->getInventoryDTO();
        $this->invMenu = InvMenu::create($dto->type);
        $this->invMenu->setName($dto->name);
        $items = $dto->items;
        foreach ($items as $item) {
            $item->setCustomName(str_replace(array_keys($this->placeHolders()), array_values($this->placeHolders()), $item->getCustomName()));
            $item->setLore(str_replace(array_keys($this->placeHolders()), array_values($this->placeHolders()), $item->getLore()));
        }
        $this->invMenu->getInventory()->setContents($dto->items);
        $this->invMenu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $item   = $transaction->getItemClicked();

            if ($this->onClick($player, $item)) {
                return $transaction->discard();
            }
            return $transaction->continue();
        });
        $this->invMenu->setInventoryCloseListener(fn (Player $player, Inventory $inventory) => $this->onClose($player, $inventory));
    }

    abstract protected function getInventoryDTO(): InventoryDTO;

    abstract protected function onClick(Player $player, Item $item): bool;

    public function getInventory(): Inventory
    {
        return $this->invMenu->getInventory();
    }

    public function send(?Player $player = null): void
    {
        if ($player === null && isset($this->player)){
            $this->invMenu->send($this->player);
            return;
        }
        $this->invMenu->send($player);
    }

    protected function onClose(Player $player, Inventory $inventory): void
    {

    }

    protected function placeHolders(): array
    {
        return [];
    }

}