<?php

namespace fenomeno\WallsOfBetrayal\Menus;

use fenomeno\WallsOfBetrayal\DTO\KingdomEnchantment;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\Utils\EnchantUtils;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\player\Player;

class EnchantingTableMenu
{

    public static function sendTo(Player $player, Kingdom $kingdom): void
    {
        $enchantments = array_values($kingdom->getEnchantments());
        if (empty($enchantments)) {
            MessagesUtils::sendTo($player, MessagesIds::ENCHANTING_TABLE_NO_ENCHANTMENTS_FOR_KINGDOM, [
                ExtraTags::KINGDOM => $kingdom->displayName,
            ]);
            return;
        }

        $menu = new MenuForm(
            title: MessagesUtils::getMessage(MessagesIds::ENCHANTING_TABLE_TITLE, [ExtraTags::KINGDOM => $kingdom->displayName]),
            text: MessagesUtils::getMessage(MessagesIds::ENCHANTING_TABLE_TEXT, [ExtraTags::KINGDOM => $kingdom->displayName]),
            options: array_map(fn(KingdomEnchantment $e) => new MenuOption(MessagesUtils::getMessage(MessagesIds::ENCHANTING_TABLE_BUTTON, [
                ExtraTags::COLOR => EnchantUtils::getEnchantmentColor($e->getEnchantment()),
                ExtraTags::EMOJI => EnchantUtils::getEnchantmentEmoji($e->getEnchantment()),
                ExtraTags::ENCHANTMENT => EnchantUtils::getEnchantmentName($e->getEnchantment()),
                ExtraTags::LEVEL => EnchantUtils::formatLevel($e->level),
                ExtraTags::COST => $e->cost,
            ])), $enchantments),
            onSubmit: function(Player $player, int $selectedOption) use ($enchantments, $kingdom): void{
                /** @var KingdomEnchantment $enchantment */
                $enchantment = $enchantments[$selectedOption];
                if ($enchantment === null) {
                    MessagesUtils::sendTo($player, MessagesIds::ENCHANTING_TABLE_INVALID_ENCHANTMENT);
                    return;
                }

                if (! $kingdom->canAffordEnchanting($enchantment)) {
                    MessagesUtils::sendTo($player, MessagesIds::ENCHANTING_TABLE_NOT_ENOUGH_RESOURCES, [ExtraTags::COST => $enchantment->cost,]);
                    return;
                }

                $kingdom->applyEnchantmentToPlayer($player, $enchantment);
            }
        );

        $player->sendForm($menu);
    }
}