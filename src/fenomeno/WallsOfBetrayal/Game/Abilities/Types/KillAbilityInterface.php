<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Types;

use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use pocketmine\player\Player;

interface KillAbilityInterface extends AbilityInterface
{

    public function onKill(Player $killer, Player $victim): void;

}