<?php

namespace fenomeno\WallsOfBetrayal\Game\Season;

use fenomeno\WallsOfBetrayal\Commands\Arguments\SeasonArgument;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Seasons\SaveSeasonPayload;
use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonEndEvent;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonStartEvent;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonPauseEvent;
use fenomeno\WallsOfBetrayal\Events\Season\SeasonResumeEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Season\FailedToCreateSeasonException;
use fenomeno\WallsOfBetrayal\Exceptions\Season\FailedToEndSeasonException;
use fenomeno\WallsOfBetrayal\Exceptions\Season\FailedToUpdateSeasonException;
use fenomeno\WallsOfBetrayal\Exceptions\Season\NoActiveSeasonException;
use fenomeno\WallsOfBetrayal\Exceptions\Season\NoSeasonException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\Server;
use Throwable;

class SeasonManager
{
    /** @var SeasonDTO|null Current Season */
    private ?SeasonDTO $currentSeason = null;

    private bool $seasonActive = false;

    /** @var array<int, array> [seasonId => seasonData] */
    private array $seasonHistory = [];

    private int $lastSeasonNumber = 0;

    public function __construct(private readonly Main $main)
    {
        $this->loadCurrentSeason();

        $this->loadSeasonHistory();
    }

    private function loadCurrentSeason(): void
    {
        Await::f2c(function() {
            try {
                /** @var null|SeasonDTO $seasonData */
                $seasonData = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->loadCurrentSeason();

                if ($seasonData !== null) {
                    $this->currentSeason    = $seasonData;
                    $this->seasonActive     = $seasonData->isActive;
                    $this->lastSeasonNumber = $seasonData->seasonNumber;

                    SeasonArgument::$VALUES[strtolower($seasonData->name)] = $seasonData;

                    if ($this->seasonActive) {
                        $this->main->getPhaseManager()->setEnabled(true);
                    }

                    $this->main->getLogger()->info("Current season loaded: #" . $seasonData->seasonNumber . " - " . $seasonData->name);
                } else {
                    $this->main->getLogger()->info("No active season found.");
                }
            } catch (Throwable $e) {
                Utils::onFailure($e, null, "Failed to load current season: " . $e->getMessage());
            }
        });
    }

    private function loadSeasonHistory(): void
    {
        Await::f2c(function() {
            try {
                $history = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->loadSeasonHistory();

                if (! empty($history)) {
                    $this->seasonHistory = $history;
                    /** @var SeasonDTO $season */
                    foreach ($history as $season) {
                        if ($season->seasonNumber > $this->lastSeasonNumber) {
                            $this->lastSeasonNumber = $season->seasonNumber;
                        }
                        SeasonArgument::$VALUES[strtolower($season->name)] = $season;
                    }
                    $this->main->getLogger()->info("Loaded " . count($history) . " past seasons into history.");
                }
            } catch (Throwable $e) {
                Utils::onFailure($e, null, "Failed to load seasons history: " . $e->getMessage());
            }
        });
    }

    /**
     * @return Generator<SeasonDTO>
     * @throws
     */
    public function startNewSeason(string $name, string $theme, string $description, int $durationDays = 30): Generator
    {
        if ($this->seasonActive && $this->currentSeason !== null) {
            yield from $this->endCurrentSeason(MessagesUtils::getMessage(MessagesIds::SEASON_ENDED_NEW_SEASON));
        }

        $seasonNumber = $this->lastSeasonNumber + 1;
        $startTime    = time();
        $endTime      = $startTime + ($durationDays * 24 * 60 * 60);

        $newSeason = new SeasonDTO(
            id: 0,
            seasonNumber: $seasonNumber,
            name: $name,
            theme: $theme,
            description: $description,
            startTime: $startTime,
            plannedEndTime: $endTime,
            actualEndTime: null,
            isActive: true,
            properties: json_encode([
                'specialEvents' => [], // TODO
                'rewards' => [], // TODO
                'challenges' => [] // TODO
            ])
        );

        /** @var null|SeasonDTO $savedSeason */
        $savedSeason = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->saveSeason(new SaveSeasonPayload($newSeason));
        if ($savedSeason === null) {
            throw new FailedToCreateSeasonException("Failed to create new season in the database.");
        }

        $this->currentSeason    = $savedSeason;
        $this->seasonActive     = true;
        $this->lastSeasonNumber = $seasonNumber;

        $this->main->getPhaseManager()->setEnabled(true);
        $this->main->getPhaseManager()->resetDays();

        $event = new SeasonStartEvent($savedSeason);
        $event->call();

        $this->announceToAllPlayers(MessagesIds::SEASON_STARTED, [
            ExtraTags::NUMBER   => $seasonNumber,
            ExtraTags::NAME     => $name,
            ExtraTags::THEME    => $theme,
            ExtraTags::DURATION => $durationDays
        ]);

        return $savedSeason;
    }

    /**
     * @param string $reason
     * @return Generator<SeasonDTO>
     * @throws NoActiveSeasonException|FailedToEndSeasonException
     */
    public function endCurrentSeason(string $reason = "End of Season"): Generator
    {
        if (! $this->seasonActive || $this->currentSeason === null) {
            throw new NoActiveSeasonException("No active season to end.");
        }

        $this->currentSeason->isActive      = false;
        $this->currentSeason->actualEndTime = time();

        $properties = json_decode($this->currentSeason->properties, true);
        $properties['ended']     = true;
        $properties['endTime']   = time();
        $properties['endReason'] = $reason;
        $this->currentSeason->properties = json_encode($properties);

        /** @var null|SeasonDTO $updatedSeason */
        $updatedSeason = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->updateSeason(new SaveSeasonPayload($this->currentSeason));
        if ($updatedSeason === null) {
            throw new FailedToEndSeasonException("Failed to end season in the database.");
        }

        $this->main->getPhaseManager()->setEnabled(false);

        $event = new SeasonEndEvent($updatedSeason, $reason);
        $event->call();

        $this->seasonHistory[$updatedSeason->id] = $updatedSeason;

        $this->announceToAllPlayers(MessagesIds::SEASON_ENDED, [
            ExtraTags::NUMBER => $updatedSeason->seasonNumber,
            ExtraTags::NAME   => $updatedSeason->name,
            ExtraTags::REASON => $reason
        ]);
        /**
         * TODO AFFICHER LES STATS DES KINGDOMS
         */

//       TODO   yield from $this->distributeSeasonRewards($updatedSeason);

        $this->seasonActive = false;
        return $updatedSeason;
    }

    /**
     * @throws NoActiveSeasonException
     * @throws FailedToUpdateSeasonException
     */
    public function pauseCurrentSeason(string $reason = "Season paused"): Generator
    {
        if (! $this->seasonActive || $this->currentSeason === null) {
            throw new NoActiveSeasonException("No active season to pause.");
        }

        $this->main->getPhaseManager()->setEnabled(false);

        $properties = json_decode($this->currentSeason->properties, true);
        $properties['paused']      = true;
        $properties['pauseTime']   = time();
        $properties['pauseReason'] = $reason;
        $this->currentSeason->properties = json_encode($properties);

        /** @var null|SeasonDTO $updatedSeason */
        $updatedSeason = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->updateSeason(new SaveSeasonPayload($this->currentSeason));
        if ($updatedSeason === null){
            throw new FailedToUpdateSeasonException("Failed to pause the current season in the database.");
        }

        $event = new SeasonPauseEvent($updatedSeason, $reason);
        $event->call();

        $this->announceToAllPlayers(MessagesIds::SEASON_PAUSED, [
            ExtraTags::NUMBER => $updatedSeason->seasonNumber,
            ExtraTags::NAME   => $updatedSeason->name,
            ExtraTags::REASON => $reason
        ]);

        return $updatedSeason;
    }

    /**
     * @throws NoSeasonException
     */
    public function resumeCurrentSeason(): Generator
    {
        if ($this->currentSeason === null) {
            throw new NoSeasonException("No season to resume.");
        }

        $properties = json_decode($this->currentSeason->properties, true);
        if (! isset($properties['paused']) || !$properties['paused']) {
            throw new NoSeasonException("The current season is not paused.");
        }

        $pauseDuration = time() - $properties['pauseTime'];
        $this->currentSeason->plannedEndTime += $pauseDuration;

        $properties['paused']             = false;
        $properties['resumeTime']         = time();
        $properties['totalPauseDuration'] = ($properties['totalPauseDuration'] ?? 0) + $pauseDuration;
        $this->currentSeason->properties  = json_encode($properties);

        $updatedSeason = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->updateSeason(new SaveSeasonPayload($this->currentSeason));

        $this->main->getPhaseManager()->setEnabled(true);
        $this->seasonActive = true;

        $event = new SeasonResumeEvent($updatedSeason, $pauseDuration);
        $event->call();

        $this->announceToAllPlayers(MessagesIds::SEASON_RESUMED, [
            ExtraTags::NUMBER   => $updatedSeason->seasonNumber,
            ExtraTags::NAME     => $updatedSeason->name,
            ExtraTags::DURATION => DurationParser::getReadableDuration($pauseDuration)
        ]);
        var_dump(DurationParser::getReadableDuration($pauseDuration));
        var_dump(DurationParser::getReadableDuration($pauseDuration));
        var_dump(DurationParser::getReadableDuration($pauseDuration));
        var_dump(DurationParser::getReadableDuration($pauseDuration));
        var_dump(DurationParser::getReadableDuration($pauseDuration));

        return $updatedSeason;
    }

    /**
     * TODO
     */
//    private function calculatePlayerRewards(int $rank, array $stats): array
//    {
//        switch ($rank) {
//            case 1:
//                $rewards['coins'] = 1000;
//                $rewards['xp'] = 500;
//                $rewards['items'] = ['trophy_gold', 'season_crown', 'legendary_chest'];
//                break;
//            case 2:
//                $rewards['coins'] = 750;
//                $rewards['xp'] = 350;
//                $rewards['items'] = ['trophy_silver', 'epic_chest'];
//                break;
//            case 3:
//                $rewards['coins'] = 500;
//                $rewards['xp'] = 250;
//                $rewards['items'] = ['trophy_bronze', 'rare_chest'];
//                break;
//            case 4:
//            case 5:
//                $rewards['coins'] = 300;
//                $rewards['xp'] = 150;
//                $rewards['items'] = ['uncommon_chest'];
//                break;
//            default: // 6-10
//                $rewards['coins'] = 200;
//                $rewards['xp'] = 100;
//                $rewards['items'] = ['common_chest'];
//                break;
//        }
//
//        // Bonus basés sur les statistiques
//        if (isset($stats['kills']) && $stats['kills'] > 50) {
//            $rewards['items'][] = 'killer_badge';
//        }
//
//        if (isset($stats['walls_destroyed']) && $stats['walls_destroyed'] > 20) {
//            $rewards['items'][] = 'wall_breaker_badge';
//        }
//
//        if (isset($stats['days_survived']) && $stats['days_survived'] > 15) {
//            $rewards['items'][] = 'survivor_badge';
//        }
//
//        return $rewards;
//    }

    private function announceToAllPlayers(string $messageId, array $tags = []): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            MessagesUtils::sendTo($player, $messageId, $tags);
        }
    }

    public function isSeasonActive(): bool
    {
        return $this->seasonActive;
    }

    public function getCurrentSeason(): ?SeasonDTO
    {
        return $this->currentSeason;
    }

    public function getSeasonHistory(): array
    {
        return $this->seasonHistory;
    }

    public function getSeasonById(int $seasonId): Generator
    {
        return Await::promise(function($resolve, $reject) use ($seasonId) {
            try {
                if (isset($this->seasonHistory[$seasonId])) {
                    $resolve($this->seasonHistory[$seasonId]);
                    return;
                }

                if ($this->currentSeason !== null && $this->currentSeason->id === $seasonId) {
                    $resolve($this->currentSeason);
                    return;
                }

                $season = yield from $this->main->getDatabaseManager()->getSeasonsRepository()->loadSeasonById(new IdPayload($seasonId));

                if ($season !== null) {
                    $this->seasonHistory[$seasonId] = $season;
                }

                $resolve($season);
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    public function isSeasonNameExists(string $name): bool
    {
        if ($this->currentSeason !== null && strtolower($name) === strtolower($this->currentSeason->name)) {
            return true;
        }

        return in_array(strtolower($name), array_map('strtolower', array_map(fn(SeasonDTO $s) => $s->name, $this->seasonHistory)), true);
    }

    public function getAllSeasons(): array
    {
        $seasons = $this->seasonHistory;
        if ($this->currentSeason !== null) {
            $seasons[] = $this->currentSeason;
        }
        return $seasons;
    }

}
