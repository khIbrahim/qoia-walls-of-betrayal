<?php

namespace fenomeno\WallsOfBetrayal\Game\Phase;

use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use fenomeno\WallsOfBetrayal\Enum\WallStateEnum;
use fenomeno\WallsOfBetrayal\Events\PhaseChangeEvent;
use fenomeno\WallsOfBetrayal\Events\WallFallEvent;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

class PhaseManager
{

    private bool $enabled    = true;

    private Config $config;

    private PhaseEnum $currentPhase = PhaseEnum::PAUSE;
    private int $currentDay = 0;
    private int $elapsedSeconds = 0;

    public function __construct(private readonly Main $main)
    {
        $this->config = new Config($main->getDataFolder() . 'game.json', Config::JSON);

        if ($this->config->exists('phase')){
            $this->currentPhase = PhaseEnum::tryFrom($this->config->get('phase')) ?? PhaseEnum::PAUSE;
        }
        if ($this->config->exists("enabled")){
            $this->enabled = (bool) $this->config->get("enabled");
        }
        if ($this->config->exists("day")){
            $this->currentDay = (int) $this->config->get("day", 0);
        }

        $this->main->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(fn() => $this->tick()),
            20
        );
    }

    public function tick(): void {
        if(! $this->enabled){
            return;
        }

        $this->elapsedSeconds++;
        if ($this->elapsedSeconds < WobConfig::getDayLength()) {
            return;
        }

        $this->elapsedSeconds = 0;
        $this->advanceDayOrPhase();
    }

    private function advanceDayOrPhase(): void {
        $ev = new DayChangeEvent($this->currentDay, $this->currentDay + 1);
        $ev->call();
        if($ev->isCancelled()){
            return;
        }

        $this->currentDay++;
        $this->save();

        $this->main->getLogger()->info("Day changed: " . $this->currentDay);

        if($this->canChangePhase()){
            $this->changePhase($this->currentPhase->next());
        }
    }

    private function changePhase(PhaseEnum $newPhase): void {
        if ($newPhase === $this->currentPhase) {
            return;
        }

        $ev = new PhaseChangeEvent($this->currentPhase, $newPhase);
        $ev->call();
        if($ev->isCancelled()){
            return;
        }

        $this->currentPhase = $newPhase;
        $this->save();
        $this->main->getLogger()->info("Phase changed: " . $newPhase->name);

        if ($newPhase === PhaseEnum::BATTLE) {
            $ev = new WallFallEvent();
            $ev->call();
        }
    }

    public function getCurrentPhase(): PhaseEnum
    {
        return $this->currentPhase;
    }

    public function getWallState(): WallStateEnum
    {
        return $this->currentPhase->wallState();
    }

    public function getCurrentDay(): int
    {
        return $this->currentDay;
    }

    public function canChangePhase(): bool
    {
        $cumulative = 0;
        foreach (WobConfig::getPhaseLengths() as $length) {
            $cumulative += $length;
            if ($this->currentDay <= $cumulative){
                return true;
            }
        }

        return false;
    }

    /** @throws */
    public function save(): void
    {
        $this->config->set("phase", $this->currentPhase->value);
        $this->config->set("day", $this->currentDay);
        $this->config->set("enabled", $this->enabled);

        $this->config->save();
    }

}