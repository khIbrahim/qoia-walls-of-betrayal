<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KingdomTopSubCommand extends WSubCommand
{

    private const FILTER_ARGUMENT = 'filter';
    private const DEFAULT_FILTER = 'kills';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new KingdomDataFilterArgument(self::FILTER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerKingdom = null;
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            if ($session->isLoaded()) {
                $playerKingdom = $session->getKingdom();
            }
        }

        $filter = $args[self::FILTER_ARGUMENT] ?? self::DEFAULT_FILTER;

        $this->displayKingdomLeaderboard($sender, $filter, $playerKingdom);
    }

    private function displayKingdomLeaderboard(CommandSender $sender, string $filter, ?Kingdom $playerKingdom = null): void
    {
        $kingdoms = $this->getKingdomStats();

        MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_TOP_HEADER, [ExtraTags::FILTER => strtoupper($filter)]);

        uasort($kingdoms, fn($a, $b) => $b[$filter] <=> $a[$filter]);

        $position = 1;
        foreach ($kingdoms as $kingdomId => $stats) {
            $kingdom = $this->main->getKingdomManager()->getKingdomById($kingdomId);
            if ($kingdom === null) continue;

            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_TOP_ENTRY, [
                ExtraTags::POSITION => $position,
                ExtraTags::KINGDOM => $kingdom->displayName,
                ExtraTags::DATA => $stats[$filter],
            ]);

            $position++;
        }

        if ($playerKingdom !== null) {
            $playerPosition = $this->getKingdomPosition($playerKingdom->id, $filter, $kingdoms);
            MessagesUtils::sendTo($sender, MessagesIds::KINGDOMS_TOP_FOOTER, [
                ExtraTags::KINGDOM => $playerKingdom->displayName,
                ExtraTags::POSITION => $playerPosition
            ]);
        }
    }

    private function getKingdomStats(): array
    {
        $kingdoms = [];
        foreach ($this->main->getKingdomManager()->getKingdoms() as $kingdom) {
            $kingdomData = $kingdom->getKingdomData();
            $kingdoms[$kingdom->id] = [
                KingdomDataFilterArgument::KILLS => $kingdomData->kills,
                KingdomDataFilterArgument::DEATHS => $kingdomData->deaths,
                KingdomDataFilterArgument::XP => $kingdomData->xp,
                KingdomDataFilterArgument::BALANCE => $kingdomData->balance
            ];
        }
        return $kingdoms;
    }

    private function getKingdomPosition(string $kingdomId, string $filter, array $kingdoms): int
    {
        $sortedKingdoms = $kingdoms;
        uasort($sortedKingdoms, fn($a, $b) => $b[$filter] <=> $a[$filter]);

        $position = 1;
        foreach (array_keys($sortedKingdoms) as $id) {
            if ($id === $kingdomId) {
                return $position;
            }
            $position++;
        }
        return 0;
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KINGDOM_TOP);
    }
}