<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Constants\InventoriesContext;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomWorldEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerLeaveKingdomWorldEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\ServerManager;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use Throwable;

class InventoryListener implements Listener
{
    public function __construct(private readonly Main $main){}

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $player->getWorld();

        Await::f2c(function() use ($player, $world) {
            try {
                if($world->getFolderName() === ServerManager::KINGDOM_WORLD) {
                    yield from $this->main->getPlayerInventoriesManager()->loadPlayer($player, InventoriesContext::KINGDOMS);
                }
            } catch (Throwable $e) {
                Utils::onFailure($e, $player, "Failed to load kingdoms inventories for {$player->getName()}: " . $e->getMessage());
            }
        });
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $player->getWorld();

        if($world->getFolderName() === ServerManager::KINGDOM_WORLD) {
            Await::g2c(
                $this->main->getPlayerInventoriesManager()->save($player, InventoriesContext::KINGDOMS),
                function (bool $success) use ($player): void {
                    if ($success) {
                        $this->main->getLogger()->debug($player->getName() . " kingdoms inventories saved");
                    }
                },
                fn (Throwable $e) => Utils::onFailure($e, null, "Failed to save " . $player->getName() . " kingdoms inventories: " . $e->getMessage())
            );
        }
    }

    public function onJoinKingdomWorld(PlayerJoinKingdomWorldEvent $event): void
    {
        $player = $event->getPlayer();

        Await::g2c(
            $this->main->getPlayerInventoriesManager()->loadPlayer($player, InventoriesContext::KINGDOMS),
            function (bool $success) use ($player): void {
                if ($success) {
                    $this->main->getLogger()->debug("kingdom inventories loaded for" . $player->getName());
                }
            },
            fn (Throwable $e) => Utils::onFailure($e, $player, "failed to load " . $player->getName() . " kingdoms inventories: " . $e->getMessage())
        );
    }

    public function onLeaveKingdomWorld(PlayerLeaveKingdomWorldEvent $event): void
    {
        $player = $event->getPlayer();

        Await::g2c(
            $this->main->getPlayerInventoriesManager()->save($player, InventoriesContext::KINGDOMS),
            function (bool $success) use ($player): void {
                if ($success) {
                    $this->main->getLogger()->debug("saved " . $player->getName() . " kingdoms inventories");
                }
            },
            fn (Throwable $e) => Utils::onFailure($e, $player, "Failed to load " . $player->getName() . " kingdom inventories: " . $e->getMessage())
        );
    }
}
