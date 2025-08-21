<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Report;

use fenomeno\WallsOfBetrayal\Class\Punishment\Report;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyReported;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class ReportCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';
    private const REASON_ARGUMENT = 'reason';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, false));
        $this->registerArgument(1, new TextArgument(self::REASON_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = (string) $args[self::PLAYER_ARGUMENT];
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if(! $player instanceof Player){
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
            return;
        }

        $reason = (string) $args[self::REASON_ARGUMENT];
        Await::f2c(function () use ($sender, $player, $reason) {
            try {
                /** @var Report $report */
                $report = yield from $this->main->getPunishmentManager()->getReportManager()->reportPlayer($player->getName(), $sender->getName(), $reason);

                MessagesUtils::sendTo($sender, MessagesIds::REPORT_SUCCESS, [
                    ExtraTags::PLAYER => $report->getTarget(),
                    ExtraTags::REASON => $report->getReason()
                ]);

                Await::g2c($this->main->getPunishmentManager()->addToHistory($report));
            } catch (PlayerAlreadyReported) {
                MessagesUtils::sendTo($sender, MessagesIds::ALREADY_REPORTED, [ExtraTags::PLAYER => $player]);
            } catch (Throwable $e){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("An error occurred while reporting player {$player->getName()} for reason $reason: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::REPORT);
    }
}