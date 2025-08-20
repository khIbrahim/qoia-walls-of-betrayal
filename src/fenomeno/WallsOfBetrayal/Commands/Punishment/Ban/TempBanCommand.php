<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Ban;

use fenomeno\WallsOfBetrayal\Commands\Arguments\DurationArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyBannedException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class TempBanCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';
    private const DURATION_ARGUMENT = 'duration';
    private const REASON_ARGUMENT = 'reason';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, false));
        $this->registerArgument(1, new DurationArgument(self::DURATION_ARGUMENT, false));
        $this->registerArgument(2, new TextArgument(self::REASON_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target     = strtolower((string) $args[self::PLAYER_ARGUMENT]);
        $expiration = (int) $args[self::DURATION_ARGUMENT];
        $staff      = $sender->getName();
        $reason     = $args[self::REASON_ARGUMENT] ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON, [ExtraTags::STAFF  => $staff]);

        Await::f2c(function () use($expiration, $sender, $staff, $reason, $target){
            try {
                $ban = yield from $this->main->getPunishmentManager()->banPlayer($target, $reason, $staff, $expiration);

                MessagesUtils::sendTo($sender, MessagesIds::BAN_TARGET_BANNED, [
                    ExtraTags::PLAYER   => $ban->getTarget(),
                    ExtraTags::STAFF    => $ban->getStaff(),
                    ExtraTags::REASON   => $ban->getReason(),
                    ExtraTags::DURATION => $ban->getDurationText()
                ]);

                if (($player = $sender->getServer()->getPlayerByPrefix($ban->getTarget())) !== null && $player->isOnline()){
                    $player->kick(MessagesUtils::getMessage(MessagesIds::BAN_SCREEN_MESSAGE, [
                        ExtraTags::PLAYER   => $ban->getTarget(),
                        ExtraTags::STAFF    => $ban->getStaff(),
                        ExtraTags::REASON   => $ban->getReason(),
                        ExtraTags::DURATION => $ban->getDurationText()
                    ]));
                }
            } catch (PlayerAlreadyBannedException) {
                MessagesUtils::sendTo($sender, MessagesIds::ALREADY_BANNED, [ExtraTags::PLAYER => $target]);
            } catch (Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("An error occurred while temp-banning player $target: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::TEMP_BAN);
    }
}