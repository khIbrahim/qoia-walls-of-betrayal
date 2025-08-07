<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit\RequirementHandlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Game\Kit\KitRequirement;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

class EntityRequirementHandler implements RequirementHandlerInterface
{

    public function handle(Player $player, KitRequirement $requirement, Kit $kit, mixed $context = null): bool
    {
        if(! $context instanceof Entity){
            return false;
        }

        if (strtolower($context::getNetworkTypeId()) !== strtolower($requirement->getTarget())){
            return false;
        }

        $requirement->incrementProgress();
        MessagesUtils::sendTo($player, 'kits.onProgress', ['{KIT}' => $kit->getDisplayName()]);
        return $requirement->isComplete();
    }
}