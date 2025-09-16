<?php

namespace fenomeno\WallsOfBetrayal\Game\BossBar;

use fenomeno\WallsOfBetrayal\DTO\SeasonDTO;
use fenomeno\WallsOfBetrayal\libs\xenialdan\apibossbar\DiverseBossBar;
use fenomeno\WallsOfBetrayal\libs\xenialdan\apibossbar\PacketListener;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class SeasonBossBar
{

    private const ROTATION_INTERVAL = 10;
    private static array $ADDITIONAL_INFOS = [];
    private DiverseBossBar $bossBar;
    /** @var null|TaskHandler  */
    private ?TaskHandler $taskHandler = null;
    private int $currentInfoIndex = 0;
    private array $additionalInfos = [];

    public function __construct(private readonly Main $main){
//        self::$ADDITIONAL_INFOS = [
//            0 => MessagesIds::SEASON_BOSS_BAR_ADDITIONAL_INFO_THEME
//        ];

        $this->bossBar = new DiverseBossBar();

        if(! PacketListener::isRegistered()){
            PacketListener::register($this->main);
        }

        $this->startRotationTask();
    }

    public function addPlayer(Player $player): self
    {
        $this->bossBar->addPlayer($player);
        $this->updateForPlayer($player);

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        $this->bossBar->removePlayer($player);

        return $this;
    }

    public function showToAll(): self
    {
        $this->bossBar->showToAll();

        return $this;
    }

    public function hideFromAll(): self
    {
        $this->bossBar->hideFromAll();

        return $this;
    }

    public function updateForPlayer(Player $player): self
    {
        $season = $this->main->getSeasonManager()->getCurrentSeason();
        if ($season === null){
            $this->bossBar->setTitleFor([$player], $this->formatSeasonTitle());
            $this->bossBar->setColorFor([$player], BossBarColor::RED);
            $this->bossBar->setPercentageFor([$player], 0.25);

            return $this;
        }

        $title     = $this->formatSeasonTitle($season);
        $subTitle  = $this->formatSeasonSubTitle($season);
        $fullTitle = $title . "\n\n" . $subTitle;

        $this->bossBar->setTitleFor([$player], $fullTitle);
        $this->bossBar->setColorFor([$player], $this->getSeasonColor($season));
        $this->bossBar->setPercentageFor([$player], $this->getSeasonPercentage($season));


        $this->bossBar->showTo([$player]);
        return $this;
    }

    public function updateForAll(): self
    {
        foreach ($this->bossBar->getPlayers() as $player){
            if ($player->isConnected()) $this->updateForPlayer($player);
        }

        return $this;
    }

    public function startRotationTask(): void
    {
        $this->taskHandler?->cancel();

        $this->taskHandler = $this->main->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                if(count(self::$ADDITIONAL_INFOS) > 0){
                    $this->currentInfoIndex = ($this->currentInfoIndex + 1) % count(self::$ADDITIONAL_INFOS);
                }
                $this->updateForAll();
            }),
            self::ROTATION_INTERVAL * 20
        );
    }

    private function formatSeasonTitle(?SeasonDTO $season = null): string
    {
        if($season === null){
            return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_NO_SEASON);
        }
        $statusSymbol = $this->getStatusSymbol($season);

        return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_TITLE, [
            ExtraTags::COLOR  => $this->getSeasonColor($season),
            ExtraTags::NUMBER => $season->seasonNumber,
            ExtraTags::NAME   => $season->name,
            ExtraTags::STATUS => $statusSymbol
        ]);
    }

    private function formatSeasonSubtitle(SeasonDTO $season): string
    {
        $additionalInfo = MessagesUtils::getMessage(
            self::$ADDITIONAL_INFOS[$this->currentInfoIndex] ?? "",
        );
        $timeInfo = $this->getTimeInfo($season);
        $themeInfo = MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_ADDITIONAL_INFO_THEME, [
            ExtraTags::THEME => $season->theme
        ]);

        return $themeInfo . " " . $timeInfo . " " . $additionalInfo;
    }

    private function getTimeInfo(SeasonDTO $season): string
    {
        if ($season->isPaused()) {
            return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_TIME_INFO_PAUSED);
        }

        $remainingDays = $season->getRemainingDays();

        if ($remainingDays <= 1) {
            $hours = $season->getRemainingHours();
            return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_TIME_INFO_LESS_THAN_A_DAY, [
                ExtraTags::HOURS => $hours
            ]);
        } elseif ($remainingDays <= 3) {
            return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_TIME_INFO_FEW_DAYS, [
                ExtraTags::DAYS => $remainingDays
            ]);
        } else {
            return MessagesUtils::getMessage(MessagesIds::SEASON_BOSS_BAR_TIME_INFO_MANY_DAYS, [
                ExtraTags::DAYS => $remainingDays
            ]);
        }
    }

    private function getSeasonPercentage(SeasonDTO $season): float
    {
        if (!$season->isActive || $season->isPaused()) {
            return 0.5;
        }

        $totalDays     = $season->getDurationDays();
        $remainingDays = $season->getRemainingDays();

        if ($totalDays <= 0) {
            return 1.0;
        }

        return max(0.05, min(1.0, $remainingDays / $totalDays));
    }

    public function getPlayers(): array
    {
        return $this->bossBar->getPlayers();
    }

    private function getStatusSymbol(SeasonDTO $season): string
    {
        if (!$season->isActive) {
            return "§c✖";
        }

        if ($season->isPaused()) {
            return "§e⏸";
        }

        $remainingDays = $season->getRemainingDays();

        if ($remainingDays <= 1) {
            return "§c⚠";
        } elseif ($remainingDays <= 3) {
            return "§6⚠";
        } else {
            return "§a⚔";
        }
    }

    private function getSeasonColor(SeasonDTO $season): int
    {
        if (! $season->isActive) {
            return BossBarColor::RED;
        }

        if ($season->isPaused()) {
            return BossBarColor::YELLOW;
        }

        $remainingDays = $season->getRemainingDays();

        if ($remainingDays <= 1) {
            return BossBarColor::RED;
        } elseif ($remainingDays <= 3) {
            return BossBarColor::YELLOW;
        } else {
            return BossBarColor::GREEN;
        }
    }

}