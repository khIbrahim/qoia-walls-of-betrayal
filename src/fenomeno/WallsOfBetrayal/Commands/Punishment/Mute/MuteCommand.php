<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Mute;

use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Commands\Arguments\DurationArgument;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyMutedException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class MuteCommand extends WCommand
{

    public const PLAYER_ARGUMENT = "player";
    public const DURATION_ARGUMENT = "duration";
    public const REASON_ARGUMENT = "reason";

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
        $moderator  = $sender->getName();
        $reason     = $args[self::REASON_ARGUMENT] ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON, [ExtraTags::STAFF  => $moderator]);

        Await::f2c(function () use($sender, $target, $expiration, $reason, $moderator) {
            try {
                /** @var Mute $mute */
                $mute = yield from $this->main->getPunishmentManager()->mutePlayer($target, $reason, $moderator, $expiration);

                MessagesUtils::sendTo($sender, MessagesIds::MUTE_TARGET_MUTED, [
                    ExtraTags::PLAYER   => $mute->getTarget(),
                    ExtraTags::STAFF    => $mute->getStaff(),
                    ExtraTags::DURATION => DurationParser::getReadableDuration($expiration),
                    ExtraTags::REASON   => $mute->getReason()
                ]);
            } catch (PlayerAlreadyMutedException){
                MessagesUtils::sendTo($sender, MessagesIds::ALREADY_MUTED, [
                    ExtraTags::PLAYER => $target
                ]);
            } catch (Throwable $e){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("An error occurred while muting player: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::MUTE);
    }
}