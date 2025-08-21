<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Events\Punishment\PlayerBannedEvent;
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
        if (! $this->main->getPunishmentManager()->getBanManager()->isBanned($name)) {
            return;
        }

        $ban = $this->main->getPunishmentManager()->getBanManager()->getBan($name);
        if ($ban === null) {
            return;
        }

        $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $this->main->getPunishmentManager()->getBanScreenMessage($ban));
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

    public function onBan(PlayerBannedEvent $event): void
    {
        /** @var Ban $ban */
        $ban = $event->getPunishment();
        Await::g2c(
            $this->main->getPunishmentManager()->addToHistory($ban),
            fn () => $this->main->getLogger()->info("Ban history for {$ban->getTarget()}: {$ban->getReason()} by {$ban->getStaff()} at " . date('Y-m-d H:i:s', $ban->getCreatedAt())),
            function(Throwable $e) {
                $this->main->getLogger()->error("Failed to log ban history: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $name   = $player->getName();

        if ($this->main->getPunishmentManager()->getMuteManager()->isMuted($name)) {
            $event->cancel();
            $mute = $this->main->getPunishmentManager()->getMuteManager()->getMute($name);
            if ($mute === null) {
                return;
            }

            $duration = DurationParser::getReadableDuration($mute->getExpiration());
            $message  = MessagesUtils::getMessage(MessagesIds::MUTE_MUTED, [
                ExtraTags::DURATION => $duration,
                ExtraTags::STAFF    => $mute->getStaff(),
                ExtraTags::REASON   => $mute->getReason()
            ]);
            $player->sendMessage($message);
        }
    }

}