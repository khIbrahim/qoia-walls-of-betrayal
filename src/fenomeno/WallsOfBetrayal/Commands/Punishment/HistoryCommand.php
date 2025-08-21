<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment;

use fenomeno\WallsOfBetrayal\Commands\Arguments\PunishmentArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\HistoryPayload;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\PunishmentHistoryEntry;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class HistoryCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';
    private const PUNISHMENT_ARGUMENT = 'punishment';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
        $this->registerArgument(1, new PunishmentArgument(self::PUNISHMENT_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = strtolower((string) $args[self::PLAYER_ARGUMENT]);
        $type   = $args[self::PUNISHMENT_ARGUMENT];

        Await::f2c(function() use ($type, $sender, $player) {
            try {
                $history = yield from $this->main->getPunishmentManager()->getHistory(new HistoryPayload($player, $type));

                if(empty($history)) {
                    MessagesUtils::sendTo($sender, MessagesIds::NO_PUNISHMENT_TYPE_HISTORY, [
                        ExtraTags::TYPE   => $type,
                        ExtraTags::PLAYER => $player
                    ]);
                }

                /** @var PunishmentHistoryEntry $historyEntry */
                foreach ($history as $historyEntry){
                    MessagesUtils::sendTo($sender, MessagesIds::PUNISHMENT_HISTORY_ENTRY, [
                        ExtraTags::TYPE        => $type,
                        ExtraTags::PLAYER      => $historyEntry->target,
                        ExtraTags::STAFF       => $historyEntry->staff ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON),
                        ExtraTags::REASON      => $historyEntry->reason ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON),
                        ExtraTags::DURATION    => $historyEntry->getDurationText(),
                        ExtraTags::CREATED_AT  => date("d/m/Y H:i:s", $historyEntry->createdAt),
                    ]);
                }

            } catch (Throwable $e){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Failed to load $type history of $player: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::HISTORY);
    }
}