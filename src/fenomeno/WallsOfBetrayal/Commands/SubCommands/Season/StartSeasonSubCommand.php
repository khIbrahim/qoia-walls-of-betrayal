<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Season;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Exceptions\Season\FailedToCreateSeasonException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TextArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use Throwable;


class StartSeasonSubCommand extends WSubCommand
{

    private const NAME_ARG     = "name";
    private const THEME_ARG    = "theme";
    private const DAYS_ARGUMENT = "days";
    private const DESCRIPTION_ARGUMENT = "description";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument(self::NAME_ARG, true));
        $this->registerArgument(1, new RawStringArgument(self::THEME_ARG, true));
        $this->registerArgument(2, new IntegerArgument(self::DAYS_ARGUMENT, true));
        $this->registerArgument(3, new TextArgument(self::DESCRIPTION_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($this->main->getSeasonManager()->isSeasonActive()) {
            MessagesUtils::sendTo($sender, MessagesIds::SEASON_ALREADY_ACTIVE, [
                ExtraTags::NUMBER => $this->main->getSeasonManager()->getCurrentSeason()->seasonNumber,
                ExtraTags::NAME   => $this->main->getSeasonManager()->getCurrentSeason()->name
            ]);
            return;
        }

        $name        = $args[self::NAME_ARG] ?? "Season " . ($this->main->getSeasonManager()->getCurrentSeason()?->seasonNumber + 1 ?? 1);
        if ($this->main->getSeasonManager()->isSeasonNameExists($name)) {
            MessagesUtils::sendTo($sender, MessagesIds::SEASON_NAME_EXISTS, [
                ExtraTags::NAME => $name
            ]);
            return;
        }

        $theme       = $args[self::THEME_ARG] ?? "Default";
        $duration    = $args[self::DAYS_ARGUMENT] ?? 30;
        $description = $args[self::DESCRIPTION_ARGUMENT] ?? "A new season of Walls of Betrayal";

        Await::f2c(function () use ($sender, $name, $theme, $description, $duration) {
            try {
                /** @var SeasonDTO $season */
                $season = yield from $this->main->getSeasonManager()->startNewSeason($name, $theme, $description, $duration);

                MessagesUtils::sendTo($sender, MessagesIds::SEASON_STARTED_SUCCESS, [
                    ExtraTags::NUMBER   => $season->seasonNumber,
                    ExtraTags::NAME     => $season->name,
                    ExtraTags::THEME    => $season->theme,
                    ExtraTags::DURATION => $season->getDurationDays()
                ]);
            } catch (FailedToCreateSeasonException|Throwable $e) {
                Utils::onFailure($e, $sender, "Failed to start new seasons ($name, $theme, $description, $duration) by {$sender->getName()}: " . $e->getMessage());
            }
        });
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::SEASON_START);
    }
}
