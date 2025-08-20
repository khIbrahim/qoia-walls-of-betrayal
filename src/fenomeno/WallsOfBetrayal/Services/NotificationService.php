<?php

namespace fenomeno\WallsOfBetrayal\Services;

use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\Server;

class NotificationService
{

    public static function broadcastMute(Mute $mute): void
    {
        $durationText = $mute->isPermanent()
            ? "PERMANENT"
            : $mute->getDurationText();

        MessagesUtils::sendTo(Server::getInstance(), MessagesIds::BROADCAST_MUTE, [
            ExtraTags::PLAYER => $mute->getTarget(),
            ExtraTags::STAFF  => $mute->getStaff(),
            ExtraTags::DURATION => $durationText,
            ExtraTags::REASON => $mute->getReason()
        ]);
    }

    public static function broadcastUnmute(string $player): void
    {
        MessagesUtils::sendTo(Server::getInstance(), MessagesIds::BROADCAST_UNMUTE, [
            ExtraTags::PLAYER => $player
        ]);
    }

    public static function broadcastBan(Ban $ban): void
    {
        $durationText = $ban->isPermanent()
            ? "PERMANENT"
            : $ban->getDurationText();

        MessagesUtils::sendTo(Server::getInstance(), MessagesIds::BROADCAST_BAN, [
            ExtraTags::PLAYER => $ban->getTarget(),
            ExtraTags::STAFF  => $ban->getStaff(),
            ExtraTags::DURATION => $durationText,
            ExtraTags::REASON => $ban->getReason()
        ]);
    }

    public static function broadcastUnban(string $target): void
    {
        MessagesUtils::sendTo(Server::getInstance(), MessagesIds::BROADCAST_UNBAN, [
            ExtraTags::PLAYER => $target
        ]);
    }
}