<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit\RequirementHandlers;

use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Game\Kit\KitRequirement;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

class BlockRequirementHandler implements RequirementHandlerInterface
{

    public function handle(Player $player, KitRequirement $requirement, Kit $kit, mixed $context = null): bool
    {
        if(! $context instanceof Block){
            return false;
        }

        if($context instanceof Crops && $context->getAge() < $context::MAX_AGE){
            return false;
        }

        $parsedBlock = StringToItemParser::getInstance()->lookupBlockAliases($context);
        if(! in_array($requirement->getTarget(), $parsedBlock)){
            return false;
        }

        $requirement->incrementProgress();
        MessagesUtils::sendTo($player, 'kits.onProgress', ['{KIT}' => $kit->getDisplayName()]);
        return $requirement->isComplete();
    }
}