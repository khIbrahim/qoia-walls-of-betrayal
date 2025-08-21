<?php

namespace fenomeno\WallsOfBetrayal\Commands\Staff;

use fenomeno\WallsOfBetrayal\Class\Punishment\Kick;
use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
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

class KickCommand extends WCommand
{

    private const TARGET_ARGUMENT = "target";
    private const REASON_ARGUMENT = "reason";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, false));
        $this->registerArgument(1, new TextArgument(self::REASON_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args[self::TARGET_ARGUMENT];
        $reason     = $args[self::REASON_ARGUMENT] ?? MessagesUtils::getMessage(MessagesIds::DEFAULT_REASON);

        $player = $this->main->getServer()->getPlayerByPrefix($targetName);
        if (! $player instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $targetName]);
            return;
        }

        try {
            $player->kick(MessagesUtils::getMessage(MessagesIds::KICK_SCREEN_MESSAGE, [
                ExtraTags::STAFF => $sender->getName(),
                ExtraTags::REASON => $reason
            ]));

            Await::g2c($this->main->getPunishmentManager()->addToHistory(new Kick($player->getName(), $reason, $sender->getName())));
        } catch (Throwable $e) {
            MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            $this->main->getLogger()->error("Failed to kick $targetName by {$sender->getName()}: " . $e->getMessage());
            $this->main->getLogger()->logException($e);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::KICK);
    }
}