<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Ban;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class BanListCommand extends WCommand
{

    private const PAGE_ARGUMENT = 'page';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument(self::PAGE_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $bans = $this->main->getPunishmentManager()->getActiveBans();

        if(empty($bans)){
            MessagesUtils::sendTo($sender, MessagesIds::BAN_LIST_EMPTY);
            return;
        }

        $page = $args[self::PAGE_ARGUMENT] ?? 1;
        if(! is_numeric($page) || $page < 1){
            MessagesUtils::sendTo($sender, MessagesIds::INVALID_PAGE, [ExtraTags::PAGE => $page]);
            return;
        }

        $bansPerPage = 10;
        $totalBans   = count($bans);
        $totalPages  = (int) ceil($totalBans / $bansPerPage);
        if($page > $totalPages){
            MessagesUtils::sendTo($sender, MessagesIds::PAGE_NOT_FOUND, [ExtraTags::PAGE => $page, ExtraTags::TOTAL_PAGES => $totalPages]);
            return;
        }

        $startIndex = ($page - 1) * $bansPerPage;
        $bans = array_slice($bans, $startIndex, $bansPerPage);

        $message = MessagesUtils::getMessage(MessagesIds::BAN_LIST_HEADER, [
            ExtraTags::PAGE        => $page,
            ExtraTags::TOTAL_PAGES => $totalPages,
        ]);
        foreach($bans as $ban){
            $duration = $ban->isPermanent() ? "PERMANENT" : $ban->getDurationText();
            $message .= MessagesUtils::getMessage(MessagesIds::BAN_LIST_ENTRY, [
                ExtraTags::PLAYER   => $ban->getTarget(),
                ExtraTags::REASON   => $ban->getReason(),
                ExtraTags::STAFF    => $ban->getStaff(),
                ExtraTags::DURATION => $duration
            ]);
        }
        $sender->sendMessage($message);
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::BAN_LIST);
    }
}