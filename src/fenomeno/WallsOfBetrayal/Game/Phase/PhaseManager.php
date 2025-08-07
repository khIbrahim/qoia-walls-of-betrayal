<?php

namespace fenomeno\WallsOfBetrayal\Game\Phase;

use fenomeno\WallsOfBetrayal\Enum\PhaseEnum;
use fenomeno\WallsOfBetrayal\Enum\WallStateEnum;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;

class PhaseManager
{

    private PhaseEnum $currentPhase = PhaseEnum::PAUSE;
    private Config $config;

    public function __construct(Main $main)
    {
        $this->config = new Config($main->getDataFolder() . 'game.json', Config::JSON);

        if ($this->config->exists('phase')){
            $this->currentPhase = PhaseEnum::tryFrom($this->config->get('phase')) ?? PhaseEnum::PAUSE;
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
        return $this->currentPhase->day();
    }

    /** @throws */
    public function save(): void
    {
        $this->config->set("phase", $this->currentPhase->value);

        $this->config->save();
    }

}