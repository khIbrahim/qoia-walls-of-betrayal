<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities;

use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\BloodRageAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\LavabornAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\NightVeilAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Ability\ShadowStepAbility;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\ActiveAbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\ConditionalAbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\KillAbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\UseAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\CooldownManager;
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

    public function triggerAbilityType(Player $player, string $interface, ...$args): void
    {
        foreach($this->abilities as $ability){
            if ($this->hasAbility($player, $ability->getId()) && $ability instanceof $interface){
                $this->triggerAbility($player, $ability, $args);
            }
        }
    }

    public function getPlayerAbilities(Player $player): array
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return [];
        }

        return array_filter(array_map(fn($abilityId) => $this->getAbilityById($abilityId), $session->getAbilities()), fn($ability) => $ability !== null);
    }

    /** @return string[] -> abilityId[] */
    public function getUnlockedAbilities(Player $player): array
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return [];
        }

        return $session->getAbilities();
    }

    /** @return string[] -> abilityId[] */
    public function getLockedAbilities(Player $player): array
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return [];
        }

        return array_filter(array_map(fn(AbilityInterface $ability) => $ability->getId(), $this->abilities), fn($ability) => ! $this->hasAbility($player, $ability));
    }

    public function hasAbility(Player $player, AbilityInterface|string $abilityId): bool
    {
        if($abilityId instanceof AbilityInterface){
            $abilityId = $abilityId->getId();
        }

        $session = Session::get($player);
        if (! $session->isLoaded()){
            return false;
        }

        return in_array($abilityId, $session->getAbilities());
    }

    public function isOnCooldown(Player $player, string $abilityId): bool {
        return CooldownManager::isOnCooldown($abilityId, $player->getName());
    }

    public function getCooldownRemaining(Player $player, string $abilityId): int {
        return CooldownManager::getCooldownRemaining($abilityId, $player->getName());
    }

    public function triggerAbility(Player $player, AbilityInterface $ability, ...$args): void
    {
        if ($ability instanceof ConditionalAbilityInterface && ! $ability->checkCondition($player)) {
            return;
        }

        $method = $this->getAbilityTypeMethod($ability);
        if ($method === null){
            return;
        }

        if(! method_exists($ability, $method)){
            return;
        }

        $ability->{$method}($player, ...$args);
    }

    private function getAbilityTypeMethod(AbilityInterface $ability): ?string
    {
        if ($ability instanceof KillAbilityInterface) {
            return 'onKill';
        } elseif ($ability instanceof ActiveAbilityInterface || $ability instanceof UseAbilityInterface) {
            return 'onUse';
        } else {
            return null;
        }
    }

}