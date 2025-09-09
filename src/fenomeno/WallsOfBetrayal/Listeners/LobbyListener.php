<?php
declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Server\LobbyManager;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

final class LobbyListener implements Listener {

    private readonly LobbyManager $lobbyManager;

    public function __construct(private readonly Main $main){
        $this->lobbyManager = $this->main->getServerManager()->getLobbyManager();
    }

    public function onJoin(PlayerJoinEvent $event): void{
        $event->setJoinMessage("");
        $this->lobbyManager->teleport($event->getPlayer());
    }

    public function onPlace(BlockPlaceEvent $event): void{
        $player = $event->getPlayer();
        if ($this->lobbyManager->isInLobby($player) && !$this->lobbyManager->getSettingByPlayer(LobbyManager::BUILD, $player)){
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void{
        $player = $event->getPlayer();
        if ($this->lobbyManager->isInLobby($player) && !$this->lobbyManager->getSettingByPlayer(LobbyManager::BREAK, $player)){
            $event->cancel();
        }
    }

    public function onInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();
        if ($this->lobbyManager->isInLobby($player) && !$this->lobbyManager->getSettingByPlayer(LobbyManager::INTERACT, $player)){
            $event->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void{
        $player = $event->getPlayer();
        if ($this->lobbyManager->isInLobby($player) && !$this->lobbyManager->getSettingByPlayer(LobbyManager::DROP, $player)){
            $event->cancel();
        }
    }

    public function onPickup(EntityItemPickupEvent $event): void{
        $entity = $event->getEntity();
        if ($entity instanceof Player
            && $this->lobbyManager->isInLobby($entity)
            && !$this->lobbyManager->getSettingByPlayer(LobbyManager::PICKUP, $entity)){
            $event->cancel();
        }
    }

    public function onHunger(PlayerExhaustEvent $event): void{
        $player = $event->getPlayer();
        if ($player instanceof Player && $this->lobbyManager->isInLobby($player) && ! $this->lobbyManager->getSettingByPlayer(LobbyManager::HUNGER, $player)){
            $event->cancel();
        }
    }

    public function onDamage(EntityDamageEvent $event): void{
        $victim = $event->getEntity();
        if(! $victim instanceof Player){
            return;
        }

        if(! $this->lobbyManager->isInLobby($victim)){
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();
            if ($attacker instanceof Player) {
                if (! $this->lobbyManager->getSettingByPlayer(LobbyManager::PVP, $attacker) || ! $this->lobbyManager->getSettingByPlayer(LobbyManager::PVP, $victim)) {
                    $event->cancel();
                    return;
                }
            }
        }

        if (! $this->lobbyManager->getSettingByPlayer(LobbyManager::DAMAGE, $victim)) {
            $event->cancel();
        }
    }

    public function onMove(PlayerMoveEvent $event): void{
        $player = $event->getPlayer();
        if(! $this->lobbyManager->isInLobby($player)){
            return;
        }
        if ($this->lobbyManager->getSetting(LobbyManager::VOID_TP) && $player->getPosition()->y < 2){
            $this->lobbyManager->teleport($player);
        }
    }

    private const KINGDOM_TAG = 'Kingdom';
    public function onUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        if(! $this->lobbyManager->isInLobby($player)){
            return;
        }

        $item = $event->getItem();
        if ($item->getNamedTag()->getTag(self::KINGDOM_TAG) === null){
            return;
        }

        $player->chat("/kingdom spawn");
    }
}
