<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities;

use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\BloodRageAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\LavabornAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\NightVeilAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\ShadowStepAbility;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class AbilityManager
{

    /** @var AbilityInterface[] */
    private array $abilities = [];

    public function __construct(private readonly Main $main){
        $this->registerAbility(new BloodRageAbility());
        $this->registerAbility(new ShadowStepAbility());
        $this->registerAbility(new NightVeilAbility());
        $this->registerAbility(new LavabornAbility());

        $this->main->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (){
            foreach ($this->abilities as $ability) {
                if ($ability instanceof BaseAbility) {
                    /** @var Player $player */
                    foreach ($ability->getActivePlayers() as $player) {
                        if ($player->isOnline()) {
                            $ability->tick($player);
                        }
                    }
                }
            }
        }), 20);

        $abilitiesNames = implode(", ", array_map(fn(AbilityInterface $ability) => $ability->getColor() . $ability->getName(), $this->abilities));
        $this->main->getLogger()->info("§aLoaded (" . count($this->abilities) . ") abilities: $abilitiesNames");
    }

    public function registerAbility(AbilityInterface $ability): void
    {
        $this->abilities[$ability->getId()] = $ability;
    }

    public function getAbilityById(string $id): ?AbilityInterface
    {
        return $this->abilities[$id] ?? null;
    }

    /** @return AbilityInterface[] */
    public function getAllAbilities(): array
    {
        return $this->abilities;
    }

    public function triggerAbilityType(Player $player, string $interface, string $method, ...$args): void
    {
        foreach($this->abilities as $ability){
            if($ability instanceof $interface && method_exists($ability, $method)){
                $ability->{$method}($player, ...$args);
            }
        }
    }

}