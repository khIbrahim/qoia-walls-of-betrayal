<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Abilities\Types\UseAbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\sound\PopSound;

class AbilityUseHandler
{

    public static function giveItem(Player $player, string $abilityId): void
    {
        if(! Main::getInstance()->getAbilityManager()->hasAbility($player, $abilityId)){
            MessagesUtils::sendTo($player, 'abilities.noAccess');
            return;
        }

        $ability   = Main::getInstance()->getAbilityManager()->getAbilityById($abilityId);
        if(! $ability instanceof UseAbilityInterface){
            MessagesUtils::sendTo($player, 'abilities.canNotGive');
            return;
        }

        $item = clone $ability->getIcon($player);
        $item->getNamedTag()->setString(AbilityInterface::ABILITY_TAG, $abilityId);
        if($player->getInventory()->contains($item)){
            MessagesUtils::sendTo($player, 'abilities.alreadyHasAbilityInInv');
            return;
        }

        if ($player->getInventory()->canAddItem($item)){
            $player->getInventory()->addItem($item);
        } else {
            $player->getWorld()->dropItem($player->getPosition(), $item);
        }

        MessagesUtils::sendTo($player, 'abilities.giveItem', [
            '{ABILITY}' => $ability->getColor() . $ability->getName()
        ]);
        $player->broadcastSound(new PopSound());
    }

    public static function useItem(Player $player, Item $item): bool
    {
        if ($item->getNamedTag()->getTag(AbilityInterface::ABILITY_TAG) === null){
            return false;
        }

        return self::use($player, $item->getNamedTag()->getString(AbilityInterface::ABILITY_TAG));
    }

    public static function use(Player $player, string $abilityId): bool
    {
        $ability = Main::getInstance()->getAbilityManager()->getAbilityById($abilityId);
        if (! $ability instanceof UseAbilityInterface){
            MessagesUtils::sendTo($player, 'abilities.noUse');
            return false;
        }

        if(! Main::getInstance()->getAbilityManager()->hasAbility($player, $abilityId)){
            MessagesUtils::sendTo($player, 'abilities.noAccess');
            return false;
        }

        Main::getInstance()->getAbilityManager()->triggerAbility($player, $ability, $player);
        return true;
    }

}