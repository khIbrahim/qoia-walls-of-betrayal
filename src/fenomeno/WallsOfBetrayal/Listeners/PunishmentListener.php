<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Events\Punishment\PlayerMutedEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use Throwable;

class PunishmentListener implements Listener
{

    public function __construct(private readonly Main $main){}

    /**
     * @priority MONITOR
    */
    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $name = strtolower($event->getPlayerInfo()->getUsername());
        if (! $this->main->getPunishmentManager()->isBanned($name)) {
            return;
        }

        $ban = $this->main->getPunishmentManager()->getBan($name);
        if ($ban === null) {
            return;
        }

        $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, MessagesUtils::getMessage(MessagesIds::BAN_SCREEN_MESSAGE, [
            ExtraTags::PLAYER   => $ban->getTarget(),
            ExtraTags::STAFF    => $ban->getStaff(),
            ExtraTags::REASON   => $ban->getReason(),
            ExtraTags::DURATION => $ban->getDurationText()
        ]));
    }

    public function onMute(PlayerMutedEvent $event): void
    {
        /** @var Mute $mute */
        $mute = $event->getPunishment();
        Await::g2c(
            $this->main->getPunishmentManager()->addToHistory($mute),
            fn () => $this->main->getLogger()->info("Mute history for {$mute->getTarget()}: {$mute->getReason()} by {$mute->getStaff()} at " . date('Y-m-d H:i:s', $mute->getCreatedAt())),
            function(Throwable $e) {
                $this->main->getLogger()->error("Failed to log mute history: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $name   = $player->getName();

        if ($this->main->getPunishmentManager()->isMuted($name)) {
            $event->cancel();
            $mute = $this->main->getPunishmentManager()->getMute($name);
            if ($mute === null) return;

            $duration = DurationParser::getReadableDuration($mute->getExpiration());
            $message  = MessagesUtils::getMessage(MessagesIds::MUTE_MUTED, [
                ExtraTags::DURATION => $duration,
                ExtraTags::STAFF    => $mute->getStaff(),
                ExtraTags::REASON   => $mute->getReason()
            ]);
            $player->sendMessage($message);
//            return;
        }
//
//        if ($this->isInJail($player) && ! JailConfig::isChatAllowed()){
//            $event->cancel();
//            MessagesUtils::sendTo($player, 'jail.events.chat', [], "§cVous ne pouvez pas parler en jail");
//        }
    }

}