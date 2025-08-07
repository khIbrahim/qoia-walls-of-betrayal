<?php

namespace fenomeno\WallsOfBetrayal\Listeners;

use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinWobEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\ScoreboardManager;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\KingdomConfig;
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
        $player = $event->getPlayer();

        $scoreboard = $this->scoreboard[$player] ?? null;
        if (! $scoreboard) {
            return;
        }
        $scoreboard->removeLine(KingdomConfig::SCOREBOARD_NAME, 2);
        $scoreboard->setLine(KingdomConfig::SCOREBOARD_NAME, 2,  "§7Kingdom : §r" . $event->getKingdom()->displayName ?? 'null');
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (! isset($this->scoreboard[$player])) {
            return;
        }
        $this->scoreboard[$player]->removeScoreboard(KingdomConfig::SCOREBOARD_NAME);
        unset($this->scoreboard[$player]);
    }

    private function setScoreboard(Player $player): void
    {
        $session = Session::get($player);
        $this->scoreboard[$player] = $scoreboard = new ScoreboardManager(WeakReference::create($player));
        $scoreboard->addScoreboard("§c§lWALLS §6§lof §e§lBETRAYAL", KingdomConfig::SCOREBOARD_NAME);
        $lines = [
            "§a§7",
            "§7Kingdom : §r" . $session->getKingdom()?->displayName ?? 'null',
            "§7Phase   : §f" . $this->main->getPhaseManager()->getCurrentPhase()->displayName(),
            "§7Day      : §f" . $this->main->getPhaseManager()->getCurrentDay() . "/14",
            "§7Wall      : §fÉrigé",
            "§r§7",
            "§7Score   : §f1290 pts",
            "§7",
            "§cplay.qoia.com"
        ];
        foreach($lines as $index => $line) {
            $scoreboard->setLine(KingdomConfig::SCOREBOARD_NAME, $index, $line);
        }
    }

}