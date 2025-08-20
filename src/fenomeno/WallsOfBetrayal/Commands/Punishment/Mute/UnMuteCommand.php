<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Mute;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotMutedException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class UnMuteCommand extends WCommand
{

    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = (string) $args[self::PLAYER_ARGUMENT];

        if (! $this->main->getPunishmentManager()->isMuted($target)) {
            MessagesUtils::sendTo($sender, MessagesIds::NOT_MUTED, [ExtraTags::PLAYER => $target]);
            return;
        }

        Await::f2c(function () use ($target, $sender) {
            try {
                yield from $this->main->getPunishmentManager()->unmutePlayer($target);

                MessagesUtils::sendTo($sender, MessagesIds::UNMUTE_SUCCESS, [ExtraTags::PLAYER => $target]);
            } catch (PlayerNotMutedException) {
                MessagesUtils::sendTo($sender, MessagesIds::NOT_MUTED, [ExtraTags::PLAYER => $target]);
            } catch (Throwable $e){
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Error unmuting player $target: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::UNMUTE);
    }
}