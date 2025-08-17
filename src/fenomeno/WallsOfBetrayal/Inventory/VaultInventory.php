<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\CloseVaultPayload;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use Throwable;

class VaultInventory extends WInventory
{

    private bool $dirty = false;

    public function __construct(
        private readonly array   $contents,
        private readonly ?string $targetUsername,
        private readonly ?string $targetUuid,
        private readonly int     $number = 1,
    )
    {
        parent::__construct();
    }

    protected function getInventoryDTO(): InventoryDTO
    {
        $vaultSize = WobConfig::getVaultSize();
        return new InventoryDTO("$this->targetUsername's Vault #$this->number", $vaultSize, Utils::getInvMenuSize($vaultSize), $this->contents);
    }

    protected function onClick(Player $player, Item $item, int $slot, ?string $action): bool
    {
        if (WobConfig::isItemLocked(StringToItemParser::getInstance()->lookupAliases($item)[0])){
            MessagesUtils::sendTo($player, MessagesIds::ITEM_LOCKED, [ExtraTags::ITEM => $item->getName()]);
            return true;
        }

        $this->dirty = true;
        return false;
    }

    protected function onClose(Player $player, Inventory $inventory): void
    {
        if (! $this->dirty) {
            return;
        }

        Await::f2c(function () use ($inventory, $player) {
            try {
                yield from Main::getInstance()->getDatabaseManager()->getVaultRepository()->close(
                    new CloseVaultPayload($this->targetUuid, $this->targetUsername, "", $this->number), $inventory->getContents()
                );

                MessagesUtils::sendTo($player, MessagesIds::VAULT_CLOSED, ['{NUMBER}' => $this->number, '{PLAYER}' => $this->targetUsername]);
            } catch (Throwable $e){
                MessagesUtils::sendTo($player, MessagesIds::ERROR, ['{ERR}' => $e->getMessage()]);
                Main::getInstance()->getLogger()->error("Error while closing vault #$this->number for player $this->targetUsername");
                Main::getInstance()->getLogger()->logException($e);
            }
        });
    }
}