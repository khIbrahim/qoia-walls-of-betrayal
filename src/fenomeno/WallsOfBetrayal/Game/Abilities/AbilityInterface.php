<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities;

use fenomeno\WallsOfBetrayal\Enum\AbilityRarity;
use pocketmine\player\Player;

interface AbilityInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getIcon(): string;
    public function getColor(): string;
    public function getRarity(): AbilityRarity;

    public function onEnable(Player $player): void;
    public function onDisable(Player $player): void;
    public function tick(Player $player): bool;

    public function getUsageTime(): int;
    public function getCooldown(): int;
    public function getCost(): int;

    public function sendActivationMessage(Player $player): void;
    public function sendCooldownMessage(Player $player, int $remaining): void;
    public function displayVisualEffects(Player $player): void;
}