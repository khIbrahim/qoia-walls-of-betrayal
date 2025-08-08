<?php

namespace fenomeno\WallsOfBetrayal\Game\Abilities\Types;

use pocketmine\player\Player;

interface UseAbilityInterface
{

    public function onUse(Player $player): void;

}