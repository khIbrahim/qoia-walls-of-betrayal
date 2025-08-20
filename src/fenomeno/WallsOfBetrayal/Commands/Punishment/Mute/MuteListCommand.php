<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Mute;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class MuteListCommand extends WCommand
{

    protected function prepare(): void
    {

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $mutes = $this->main->getPunishmentManager()->getActiveMutes();

        if (empty($mutes)) {
            MessagesUtils::sendTo($sender, MessagesIds::MUTE_LIST_EMPTY);
            return;
        }

        $message = MessagesUtils::getMessage(MessagesIds::MUTE_LIST_HEADER, [
            ExtraTags::COUNT => count($mutes)
        ]);

        foreach($mutes as $mute) {
            $durationLeft  = DurationParser::getReadableDuration($mute->getExpiration());
            $message      .= MessagesUtils::getMessage(MessagesIds::MUTE_LIST_ENTRY, [
                ExtraTags::PLAYER   => $mute->getTarget(),
                ExtraTags::STAFF    => $mute->getStaff(),
                ExtraTags::REASON   => $mute->getReason(),
                ExtraTags::DURATION => $durationLeft
            ]);
        }

        $sender->sendMessage($message);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::MUTE_LIST);
    }
}