<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit\RequirementHandlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Game\Kit\KitRequirement;
use pocketmine\player\Player;

interface RequirementHandlerInterface
{

    public function handle(Player $player, KitRequirement $requirement, Kit $kit, mixed $context = null): bool;

}