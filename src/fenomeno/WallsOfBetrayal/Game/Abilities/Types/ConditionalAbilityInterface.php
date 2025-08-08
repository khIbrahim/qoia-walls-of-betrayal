<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Types;

use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use pocketmine\player\Player;

interface ConditionalAbilityInterface extends AbilityInterface
{
    public function checkCondition(Player $player): bool;
}