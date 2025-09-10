<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Season;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Season\NoSeasonException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use Throwable;

class ResumeSeasonSubCommand extends WSubCommand
{

    protected function prepare(): void
    {
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $currentSeason = $this->main->getSeasonManager()->getCurrentSeason();
        if ($currentSeason === null) {
            MessagesUtils::sendTo($sender, MessagesIds::NO_ACTIVE_SEASON);
            return;
        }

        Await::f2c(function () use ($currentSeason, $sender) {
            try {
                /** @var SeasonDTO $season */
                $season = yield from $this->main->getSeasonManager()->resumeCurrentSeason();

                MessagesUtils::sendTo($sender, MessagesIds::SEASON_RESUMED_SUCCESS, [
                    ExtraTags::NUMBER  => $season->seasonNumber,
                    ExtraTags::NAME    => $season->name,
                ]);
            } catch (NoSeasonException) {
                MessagesUtils::sendTo($sender, MessagesIds::SEASON_NOT_PAUSED, [
                    ExtraTags::NUMBER => $currentSeason->seasonNumber,
                    ExtraTags::NAME   => $currentSeason->name
                ]);
            } catch (Throwable $e) {
                Utils::onFailure($e, $sender, "Failed to resume season by {$sender->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON_RESUME);
    }
}
