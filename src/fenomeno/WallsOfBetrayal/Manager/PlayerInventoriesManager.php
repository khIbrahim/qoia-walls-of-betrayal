<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Constants\InventoriesContext;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerInventoriesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\LoadPlayerInventoriesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\SavePlayerInventoriesPayload;
use fenomeno\WallsOfBetrayal\DTO\SavedPlayerInventories;
use fenomeno\WallsOfBetrayal\Exceptions\Player\FailedInventoriesTransferException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;
use Throwable;

class PlayerInventoriesManager
{

    /** @var array<string, array<string, SavedPlayerInventories>> playerName => context => inventories */
    private array $inventories = [];

    /** @var array<string, SavedPlayerInventories> context => inventories */
    private array $defaultInventories = [];

    public function __construct(private readonly Main $main)
    {
        $this->initDefaultInventories();
    }

    public function initDefaultInventories(): void
    {
        $this->registerDefaultInventories(InventoriesContext::KINGDOMS, [], [], []);
    }

    public function loadPlayer(Player $player, string $context): Generator
    {
        $updateContentsBySaved = function(Player $player, SavedPlayerInventories $savedInventories): void {
            $player->getInventory()->setContents($savedInventories->inv);
            $player->getArmorInventory()->setContents($savedInventories->armor);
            $player->getOffHandInventory()->setContents($savedInventories->offhand);
        };

        if ($this->hasSavedInventoriesByContext($player, $context)) {
            $savedInventories = $this->getInventories($player->getName(), $context) ?? $this->defaultInventories[$context] ?? null;
            if ($savedInventories === null) {
                return false;
            }

            $updateContentsBySaved($player, $savedInventories);
            return true;
        }

        $inventories = yield from $this->main->getDatabaseManager()->getPlayerInventoriesRepository()->load(new LoadPlayerInventoriesPayload(
            uuid: $player->getUniqueId()->toString(),
            context: $context
        ));

        $inventories = $inventories ?? $this->defaultInventories[$context] ?? null;
        if ($inventories === null) {
            return false;
        }

        $this->setInventories($player->getName(), $inventories);

        $updateContentsBySaved($player, $inventories);

        return true;
    }

    public function save(Player $player, string $context): Generator
    {
        $inv     = $player->getInventory()->getContents();
        $armor   = $player->getArmorInventory()->getContents();
        $offhand = $player->getOffHandInventory()->getContents();

        $this->setInventories($player->getName(), new SavedPlayerInventories($inv, $armor, $offhand, $context));

        $encodeItems = function(array $items, string $tag): string {
            return $this->main->getDatabaseManager()->getBinaryStringParser()->encode($this->main->getDatabaseManager()->writeItems($items, $tag));
        };

        yield from $this->main->getDatabaseManager()->getPlayerInventoriesRepository()->save(new SavePlayerInventoriesPayload(
            uuid: $player->getUniqueId()->toString(),
            username: $player->getName(),
            context: $context,
            inv: $encodeItems($inv, PlayerInventoriesRepositoryInterface::TAG_INVENTORY . $context),
            armor: $encodeItems($armor, PlayerInventoriesRepositoryInterface::TAG_ARMOR_INVENTORY . $context),
            offhand: $encodeItems($offhand, PlayerInventoriesRepositoryInterface::TAG_OFF_HAND_INV . $context)
        ));

        return true;
    }

    public function transfer(Player $player, string $fromContext, string $toContext): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($toContext, $fromContext, $player) {
            try {
                $saved  = yield from $this->save($player, $fromContext);
                $loaded = yield from $this->loadPlayer($player, $toContext);

                $resolve($loaded && $saved);
            } catch (Throwable $e) {
                $reject(new FailedInventoriesTransferException("Failed to transfer inventories of {$player->getName()} from $fromContext to $toContext: " . $e->getMessage(), 0, $e));
            }
        });
    }

    public function getInventories(string $playerName, string $context): ?SavedPlayerInventories
    {
        return $this->inventories[$playerName][$context] ?? null;
    }

    public function setInventories(string $playerName, SavedPlayerInventories $inventories): void
    {
        $this->inventories[$playerName][$inventories->context] = $inventories;
    }

    private function registerDefaultInventories(string $context, array $inventory, array $armorInventory, array $offhandInventory): void
    {
        $this->defaultInventories[$context] = new SavedPlayerInventories($inventory, $armorInventory, $offhandInventory, $context);
    }

    private function hasSavedInventoriesByContext(Player $player, string $context): bool
    {
        return isset($this->inventories[$player->getName()][$context]);
    }

    /**
     * Supprime les inventaires en cache pour un joueur
     */
    public function clearCache(string $playerName): void
    {
        if (isset($this->inventories[$playerName])) {
            unset($this->inventories[$playerName]);
        }
    }
}