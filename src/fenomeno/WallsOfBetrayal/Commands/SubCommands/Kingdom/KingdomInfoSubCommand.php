<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomInfoSubCommand extends WSubCommand
{
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $session = Session::get($sender);
        if (!$session->isLoaded()) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [ExtraTags::PLAYER => $sender->getName()]);
            return;
        }

        $kingdom = $session->getKingdom();
        if ($kingdom === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_IN_KINGDOM);
            return;
        }

        $this->displayKingdomInfo($sender, $kingdom);
    }

    private function displayKingdomInfo(Player $sender, Kingdom $kingdom): void
    {
        Await::f2c(function () use ($sender, $kingdom) {
            $kingdomData = $kingdom->getKingdomData();
            $onlineMembers = $kingdom->getOnlineMembers();
            $totalMembers = yield from $this->main->getDatabaseManager()->getKingdomRepository()->getTotalMembers(new IdPayload($kingdom->getId()));

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_HEADER, [
                ExtraTags::COLOR => $kingdom->color,
                ExtraTags::KINGDOM => $kingdom->displayName
            ]);

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_DESCRIPTION, [
                ExtraTags::DESCRIPTION => $kingdom->description
            ]);

            $kdr = $kingdomData->deaths > 0 ? round($kingdomData->kills / $kingdomData->deaths, 2) : $kingdomData->kills;
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_STATS, [
                ExtraTags::KILLS => $kingdomData->kills,
                ExtraTags::DEATHS => $kingdomData->deaths,
                ExtraTags::KDR => $kdr
            ]);

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_XP, [
                ExtraTags::XP => $kingdomData->xp
            ]);

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_BALANCE, [
                ExtraTags::BALANCE => $this->main->getEconomyManager()->getCurrency()->formatter->format($kingdomData->balance)
            ]);

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_MEMBERS, [
                ExtraTags::ONLINE => count($onlineMembers),
                ExtraTags::TOTAL => $totalMembers
            ]);

            if (!empty($kingdom->abilities)) {
                MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_INFO_ABILITIES, [
                    ExtraTags::ABILITIES => implode(', ', $kingdom->abilities)
                ]);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_INFO);
    }
}