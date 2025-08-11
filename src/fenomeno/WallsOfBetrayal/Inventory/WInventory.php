<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Inventory\Actions\InventoryActions;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenu;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\transaction\InvMenuTransaction;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\transaction\InvMenuTransactionResult;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use ReflectionException;
use Throwable;

abstract class WInventory {

    protected InvMenu $invMenu;
    protected InventoryDTO $dto;

    public function __construct() {
        $this->dto = $this->getInventoryDTO();
        $this->invMenu = InvMenu::create($this->dto->type);

        $phKeys = array_keys($this->placeholders());
        $phVals = array_values($this->placeholders());

        $this->invMenu->setName(str_replace($phKeys, $phVals, $this->dto->name));

        $items = $this->dto->items;
        foreach ($items as $slot => $item) {
            $item->setCustomName(str_replace($phKeys, $phVals, $item->getCustomName()));
            $item->setLore(array_map(fn($l) => str_replace($phKeys, $phVals, $l), $item->getLore()));
            $items[$slot] = $item;
        }

        $this->invMenu->getInventory()->setContents($items);

        $this->invMenu->setListener(function (InvMenuTransaction $tx): InvMenuTransactionResult {
            $player = $tx->getPlayer();
            $slot   = $tx->getAction()->getSlot();
            $item   = $tx->getItemClicked();

            $action = $this->dto->actions[$slot] ?? null;
            try {
                if ($this->onClick($player, $item, $slot, $action)) {
                    return $tx->discard();
                }
            } catch (Throwable $e){
                $player->removeCurrentWindow();
                MessagesUtils::sendTo($player, 'common.errorOnInvTransaction', ['{ERR}' => $e->getMessage()]);
                Main::getInstance()->getLogger()->logException($e);
                return $tx->discard();
            }
            return $tx->continue();
        });

        $this->invMenu->setInventoryCloseListener(fn(Player $p, Inventory $i) => $this->onClose($p, $i));
    }

    abstract protected function getInventoryDTO(): InventoryDTO;

    /**
     * @throws ReflectionException
     */
    protected function onClick(Player $player, Item $item, int $slot, ?string $action): bool {
        if ($action){
            $args = Utils::getChildPropertiesValues($this);

            return InventoryActions::handleAction(
                $player,
                $item,
                $slot,
                $action,
                $this,
                ...$args
            );
        }

        $handledByRandomItem = InventoryActions::handleItem($player, $item, $slot, $this, ...Utils::getChildPropertiesValues($this));

        return $handledByRandomItem === true || $this->onClickLegacy($player, $item);
    }


    protected function onClickLegacy(Player $player, Item $item): bool { return false; }

    protected function onClose(Player $player, Inventory $inventory): void {}

    protected function placeholders(): array { return []; }

    public function getInventory(): Inventory { return $this->invMenu->getInventory(); }

    public function send(?Player $player = null): void {
        if ($player === null && isset($this->player)) {
            $this->invMenu->send($this->player); return;
        }
        $this->invMenu->send($player);
    }
}