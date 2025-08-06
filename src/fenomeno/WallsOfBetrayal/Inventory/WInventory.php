<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

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

    abstract protected function getInventoryDTO(): object;

    abstract protected function onClick(Player $player, Item $item): bool;

    public function getInventory(): Inventory
    {
        return $this->invMenu->getInventory();
    }

    public function send(Player $player): void
    {
        $this->invMenu->send($player);
    }

    protected function onClose(Player $player, Inventory $inventory): void
    {

    }

}