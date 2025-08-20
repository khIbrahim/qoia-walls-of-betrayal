<?php

namespace fenomeno\WallsOfBetrayal\Commands\Punishment\Ban;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotBannedException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use Throwable;

class UnBanCommand extends WCommand
{
    private const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::PLAYER_ARGUMENT, false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = strtolower((string) $args[self::PLAYER_ARGUMENT]);

        Await::f2c(function () use ($sender, $target) {
            try {
                yield from $this->main->getPunishmentManager()->unbanPlayer($target);

                MessagesUtils::sendTo($sender, MessagesIds::UNBAN_SUCCESS, [ExtraTags::PLAYER => $target]);
            } catch (PlayerNotBannedException) {
                MessagesUtils::sendTo($sender, MessagesIds::NOT_BANNED, [ExtraTags::PLAYER => $target]);
            } catch (Throwable $e) {
                MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                $this->main->getLogger()->error("Error unbanning player $target: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::UNBAN);
    }
}