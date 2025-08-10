<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Events\PhaseChangeEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinWobEvent;
use fenomeno\WallsOfBetrayal\Game\Phase\DayChangeEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\ScoreboardManager;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use WeakMap;
use WeakReference;

class ScoreboardUpdateListener implements Listener
{

    /** @var WeakMap<Player, ScoreboardManager> */
    private WeakMap $scoreboard;
    public function __construct(private readonly Main $main)
    {
        $this->scoreboard = new WeakMap();
    }

    public function onJoin(PlayerJoinWobEvent $event): void
    {
        $player = $event->getPlayer();

        $this->setScoreboard($player);
    }

    public function onKingdomJoin(PlayerJoinKingdomEvent $event): void
    {
        $this->updateScoreboard($event->getPlayer(), 2,  "§7Kingdom : §r" . $event->getKingdom()->displayName ?? 'null');
    }

    public function onPhaseChange(PhaseChangeEvent $event): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            $this->updateScoreboard($player, 2,  "§7Phase   : §f" . $event->getTo()->displayName());
        }
    }

    public function onDayChange(DayChangeEvent $event): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            $this->updateScoreboard($player, 2,  "§7Day      : §f" . $event->getTo() . "/" . WobConfig::getTotalDays());
        }
    }

    private function updateScoreboard(Player $player, int $line, string $text): void
    {
        $scoreboard = $this->scoreboard[$player] ?? null;
        if (! $scoreboard) {
            return;
        }
        $scoreboard->removeLine(WobConfig::SCOREBOARD_NAME, $line);
        $scoreboard->setLine(WobConfig::SCOREBOARD_NAME, $line, $text);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (! isset($this->scoreboard[$player])) {
            return;
        }
        $this->scoreboard[$player]->removeScoreboard(WobConfig::SCOREBOARD_NAME);
        unset($this->scoreboard[$player]);
    }

    private function setScoreboard(Player $player): void
    {
        $session = Session::get($player);
        $this->scoreboard[$player] = $scoreboard = new ScoreboardManager(WeakReference::create($player));
        $scoreboard->addScoreboard("§c§lWALLS §6§lof §e§lBETRAYAL", WobConfig::SCOREBOARD_NAME);
        $lines = [
            "§a§7",
            "§7Kingdom : §r" . $session->getKingdom()?->displayName ?? 'null',
            "§7Phase   : §f" . $this->main->getPhaseManager()->getCurrentPhase()->displayName(),
            "§7Day      : §f" . $this->main->getPhaseManager()->getCurrentDay() . "/" . WobConfig::getTotalDays(),
            "§7Wall      : §f" . $this->main->getPhaseManager()->getWallState()->displayName(),
            "§r§7",
            "§7Score   : §f1290 pts",
            "§7",
            "§cplay.qoia.com"
        ];
        foreach($lines as $index => $line) {
            $scoreboard->setLine(WobConfig::SCOREBOARD_NAME, $index, $line);
        }
    }

}