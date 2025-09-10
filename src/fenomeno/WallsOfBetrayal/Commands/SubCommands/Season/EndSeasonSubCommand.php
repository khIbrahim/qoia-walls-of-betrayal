<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Season;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Season\FailedToEndSeasonException;
use fenomeno\WallsOfBetrayal\Exceptions\Season\NoActiveSeasonException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use Throwable;

class EndSeasonSubCommand extends WSubCommand
{

    private const REASON_ARGUMENT = 'reason';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument(self::REASON_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $reason = $args[self::REASON_ARGUMENT] ?? MessagesUtils::defaultReason('WallsOfBetrayal System');

        Await::f2c(function () use ($sender, $reason) {
            try {
                /** @var SeasonDTO $season */
                $season = yield from $this->main->getSeasonManager()->endCurrentSeason($reason);

                MessagesUtils::sendTo($sender, MessagesIds::SEASON_ENDED_SUCCESS, [
                    ExtraTags::NUMBER => $season->seasonNumber,
                    ExtraTags::NAME   => $season->name,
                    ExtraTags::REASON => $reason
                ]);
            } catch (NoActiveSeasonException $e) {
                MessagesUtils::sendTo($sender, MessagesIds::NO_ACTIVE_SEASON, [], $e->getMessage());
            } catch (FailedToEndSeasonException|Throwable $e) {
                Utils::onFailure($e, $sender, "Failed to end season by {$sender->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON_END);
    }
}
