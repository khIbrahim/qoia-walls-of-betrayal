<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Shop;

use fenomeno\WallsOfBetrayal\Class\Shop\ShopItem;
use fenomeno\WallsOfBetrayal\Handlers\ShopTransactionHandler;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomFormResponse;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Input;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Label;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ShopMenus {

    public const BUY_MODE  = 'buy';
    public const SELL_MODE = 'sell';

    public static function sendChooseMode(Player $player, ShopItem $shopItem): void {
        $menu = new MenuForm(
            title: MessagesUtils::getMessage('shop.choose-menu.title', ['{SHOP_ITEM}' => $shopItem->getDisplayName()]),
            text:  MessagesUtils::getMessage('shop.choose-menu.text',  ['{SHOP_ITEM}' => $shopItem->getDisplayName()]),
            options: [
                new MenuOption(MessagesUtils::getMessage('shop.choose-menu.buy')),
                new MenuOption(MessagesUtils::getMessage('shop.choose-menu.sell'))
            ],
            onSubmit: function(Player $player, int $selected) use ($shopItem): void {
                if ($selected === 0) {
                    if ($shopItem->getBuyPrice() <= 0) { MessagesUtils::sendTo($player, 'shop.canNotBuy'); return; }
                    self::sendBuyMenu($player, $shopItem);
                } else {
                    if ($shopItem->getSellPrice() <= 0) { MessagesUtils::sendTo($player, 'shop.canNotSell'); return; }
                    self::sendSellMenu($player, $shopItem);
                }
            }
        );
        $player->sendForm($menu);
    }

    private static function sendBuyMenu(Player $player, ShopItem $shopItem): void {
        $balance = Utils::formatBalance($player);
        $form = new CustomForm(
            title: MessagesUtils::getMessage('shop.buy-menu.title', ['{SHOP_ITEM}' => $shopItem->getDisplayName()]),
            elements: [
                new Label('info', MessagesUtils::getMessage('shop.buy-menu.label', [
                    '{SHOP_ITEM}'  => $shopItem->getDisplayName(),
                    '{UNIT_PRICE}' => Utils::formatCurrency($shopItem->getBuyPrice()),
                    '{BALANCE}'    => $balance
                ])),
                new Input('qty', MessagesUtils::getMessage('shop.buy-menu.input'), "1")
            ],
            onSubmit: function(Player $player, CustomFormResponse $res) use ($shopItem): void {
                $qty = Utils::parseQty($res->getString('qty'));
                if ($qty === null) { MessagesUtils::sendTo($player, 'shop.invalidQuantity'); return; }

                Await::g2c(ShopTransactionHandler::buy(player: $player, shopItem: $shopItem, count: $qty),
                    function ($total) use ($shopItem, $qty, $player) {
                        if($total){
                            MessagesUtils::sendTo($player, 'shop.bought', [
                                '{ITEM}'        => TextFormat::clean($shopItem->getDisplayName()),
                                '{QTY}'         => (string) $qty,
                                '{TOTAL_PRICE}' => (string) $total
                            ]);
                        }
                    }, fn ($er) => MessagesUtils::sendTo($player, 'shop.txnFailed', ['{ERR}' => (string) $er])
                );
            }
        );
        $player->sendForm($form);
    }

    private static function sendSellMenu(Player $player, ShopItem $shopItem): void {
        $count = $shopItem->getItem()->getCount();
        $form = new CustomForm(
            title: MessagesUtils::getMessage('shop.sell-menu.title', ['{SHOP_ITEM}' => $shopItem->getDisplayName()]),
            elements: [
                new Label('info', MessagesUtils::getMessage('shop.sell-menu.label', [
                    '{SHOP_ITEM}'  => $shopItem->getDisplayName(),
                    '{UNIT_PRICE}' => Utils::formatCurrency($shopItem->getSellPrice()),
                    '{HAVE}'       => (string) (Utils::countInInventory($player->getInventory(), $shopItem->getItem()) / $count),
                    '{COUNT}'      => (string) $count
                ])),
                new Input('qty', MessagesUtils::getMessage('shop.sell-menu.input'), "1", "1")
            ],
            onSubmit: function(Player $player, CustomFormResponse $res) use ($shopItem): void {
                $qty = Utils::parseQty($res->getString('qty'));
                if ($qty === null) { MessagesUtils::sendTo($player, 'shop.invalidQuantity'); return; }

                Await::g2c(ShopTransactionHandler::sell(
                    player: $player,
                    shopItem: $shopItem,
                    count: $qty
                ), function ($total) use ($qty, $shopItem, $player) {
                    if($total){
                        MessagesUtils::sendTo($player, 'shop.sold', [
                            '{ITEM}' => TextFormat::clean($shopItem->getDisplayName()),
                            '{QTY}' => (string)$qty,
                            '{TOTAL_PRICE}' => (string)$total
                        ]);
                    }
                }, function ($er) use ($player) {
                    MessagesUtils::sendTo($player, 'shop.txnFailed', ['{ERR}' => (string) $er]);
                });
            }
        );
        $player->sendForm($form);
    }

    public static function sendShopByMode(Player $player, ShopItem $shopItem, string $mode): void
    {
        if ($mode === self::BUY_MODE) {
            if ($shopItem->getBuyPrice() <= 0) { MessagesUtils::sendTo($player, 'shop.canNotBuy'); return; }
            self::sendBuyMenu($player, $shopItem);
        } else {
            if ($shopItem->getSellPrice() <= 0) { MessagesUtils::sendTo($player, 'shop.canNotSell'); return; }
            self::sendSellMenu($player, $shopItem);
        }
    }
}
