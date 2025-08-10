<?php
namespace fenomeno\WallsOfBetrayal\Game\Abilities\Types;

use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use pocketmine\player\Player;

interface ActiveAbilityInterface extends AbilityInterface
{
    public function onUse(Player $player): void;
}