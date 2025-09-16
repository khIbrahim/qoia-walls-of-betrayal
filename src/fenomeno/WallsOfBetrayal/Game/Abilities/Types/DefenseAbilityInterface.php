<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Types;

use pocketmine\player\Player;

interface DefenseAbilityInterface
{
    /**
     * Called when a player defends against an attacker
     * 
     * @param Player $defender The player defending
     * @param Player $attacker The player attacking
     */
    public function onDefense(Player $defender, Player $attacker): void;
}