<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Config\StaffConfig;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Sessions\StaffSession;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\player\Player;

class StaffListener implements Listener
{

    public function __construct(private readonly Main $main){}


    public function onUse(PlayerItemUseEvent $event): void
    {
        $player  = $event->getPlayer();
        $item    = $event->getItem();

        if (Session::get($player)->isFrozen()){
            $event->cancel();
            MessagesUtils::sendTo($player, MessagesUtils::getMessage(MessagesIds::FROZEN));
            return;
        }

        $session = StaffSession::get($player);

        if(! $session->isInStaffMode()){
            return;
        }

        if ($item->getNamedTag()->getTag(StaffConfig::STAFF_MOD_TAG) === null) {
            return;
        }

        $type = $item->getNamedTag()->getString(StaffConfig::STAFF_MOD_TYPE_TAG, "");
        if ($type != "use"){
            return;
        }

        $command = $item->getNamedTag()->getString(StaffConfig::STAFF_MOD_COMMAND_TAG, "");
        if ($command === "") {
            MessagesUtils::sendTo($player, MessagesIds::NO_COMMAND_STAFF_MOD_ITEM);
            return;
        }

        $command = str_replace(ExtraTags::PLAYER, $player->getName(), $command);
        $command = str_replace(ExtraTags::TARGET, $player->getName(), $command);
        $player->getServer()->dispatchCommand($player, $command);
    }

    public function onDamage(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getDamager();
        $target = $event->getEntity();

        if (! $player instanceof Player || !$target instanceof Player) {
            return;
        }

        $session = StaffSession::get($player);
        if (! $session->isInStaffMode()) {
            return;
        }

        $item = $player->getInventory()->getItemInHand();
        if ($item->getNamedTag()->getTag(StaffConfig::STAFF_MOD_TAG) === null) {
            return;
        }

        $type = $item->getNamedTag()->getString(StaffConfig::STAFF_MOD_TYPE_TAG, "");
        if ($type !== "hit") {
            return;
        }

        $command = $item->getNamedTag()->getString(StaffConfig::STAFF_MOD_COMMAND_TAG, "");
        if ($command === "") {
            MessagesUtils::sendTo($player, MessagesIds::NO_COMMAND_STAFF_MOD_ITEM);
            return;
        }

        $command = str_replace(ExtraTags::PLAYER, $player->getName(), $command);
        $command = str_replace(ExtraTags::TARGET, $target->getName(), $command);
        $player->getServer()->dispatchCommand($player, $command);
        $event->cancel();
    }

    public function onPacket(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        if ($packet instanceof LevelSoundEventPacket){
            $player = $event->getOrigin()->getPlayer();
            if (! $player instanceof Player) {
                return;
            }

            $session = StaffSession::get($player);
            if (! $session->isInStaffMode() ) {
                return;
            }

            $event->cancel();
        }
    }

    public function onDamage_(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();
        if(! $player instanceof Player) {
            return;
        }

        if ((StaffSession::get($player)->isInStaffMode() && ! StaffConfig::isDamageAllowed()) || StaffSession::get($player)->isVanished()) {
            $event->cancel();
        }

        $session = Session::get($player);
        if($session->isFrozen()){
            $event->cancel();
            MessagesUtils::sendTo($player, MessagesUtils::getMessage(MessagesIds::FROZEN));
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        if (StaffSession::get($player)->isInStaffMode() && ! StaffConfig::isBreakAllowed()) {
            $event->cancel();
            return;
        }

        $session = Session::get($event->getPlayer());
        if($session->isFrozen()){
            $event->cancel();
            MessagesUtils::sendTo($player, MessagesUtils::getMessage(MessagesIds::FROZEN));
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (StaffSession::get($player)->isInStaffMode() && ! StaffConfig::isPlaceAllowed()) {
            $event->cancel();
        }

        $session = Session::get($event->getPlayer());
        if ($session->isFrozen()) {
            $event->cancel();
            MessagesUtils::sendTo($player, MessagesUtils::getMessage(MessagesIds::FROZEN));
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();

        $session = StaffSession::get($player);
        if ($session->isInStaffMode() && ! StaffConfig::isDropAllowed()) {
            $event->cancel();
        }
    }

    public function onPickup(EntityItemPickupEvent $event): void
    {
        $player = $event->getEntity();
        if (! $player instanceof Player) {
            return;
        }

        $session = StaffSession::get($player);
        if ($session->isInStaffMode() && ! StaffConfig::isPickupAllowed()) {
            $event->cancel();
        }
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player  = $event->getPlayer();
        $session = StaffSession::get($player);
        $session->loadFromConfig();
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player  = $event->getPlayer();
        $session = StaffSession::get($player);
        if ($session->isInStaffMode()){
            $session->setInStaffMode(false);
        }

        $session = Session::get($event->getPlayer());
        if($session->isFrozen() && StaffConfig::isBanOnDisconnectWhileFrozen()){
            Await::g2c($this->main->getPunishmentManager()->getBanManager()->banPlayer($player->getName(), 'Quit On Freeze', 'WobPunishment'));
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $session = StaffSession::get($player);
        if ($session->isStaffChatEnabled()) {
            $event->cancel();
            $message = $event->getMessage();
            foreach($player->getServer()->getOnlinePlayers() as $online){
                if (StaffSession::get($online)->isStaffChatEnabled() || $online->hasPermission(PermissionIds::STAFF_CHAT)) {
                    MessagesUtils::sendTo($online, MessagesIds::STAFF_CHAT_FORMAT, [
                        ExtraTags::PLAYER  => $player->getName(),
                        ExtraTags::MESSAGE => $message
                    ]);
                }
            }
        }

        if (StaffSession::get($player)->isInStaffMode() && ! StaffConfig::isChatAllowed()) {
            $event->cancel();
            return;
        }

        $session = Session::get($event->getPlayer());
        if($session->isFrozen()){
            $event->cancel();
            MessagesUtils::sendTo($player, MessagesUtils::getMessage(MessagesIds::FROZEN));
        }
    }

    public function onMove(PlayerMoveEvent $event): void
    {
        $session = Session::get($event->getPlayer());
        if($session->isFrozen()){
            $event->cancel();
            MessagesUtils::sendTo($event->getPlayer(), MessagesUtils::getMessage(MessagesIds::FROZEN));
        }

    }

}