<?php

namespace fenomeno\WallsOfBetrayal\Handlers;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Class\Shop\ShopItem;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\player\Player;
use Throwable;

class ShopTransactionHandler
{

    private static array $locks = [];

    /** @throws */
    public static function buy(Player $player, ShopItem $shopItem, int $count): Generator
    {
        $unit = $shopItem->getBuyPrice();
        $total = $unit * $count;

        /** @var EconomyEntry $entry */
        $entry = yield from Main::getInstance()->getEconomyManager()->get($player, $player->getUniqueId()->toString(), true);
        if ($entry->amount < $total) {
            MessagesUtils::sendTo($player, 'shop.notEnoughMoney', [
                '{PRICE}' => (string)$total
            ]);
            return null;
        }

        $base = $shopItem->getItem();
        if (! Utils::canGive($player->getInventory(), $base, $count)) {
            MessagesUtils::sendTo($player, 'shop.notEnoughSpace');
            return null;
        }

        $name = strtolower($player->getName());
        if (isset(self::$locks[$name])) { MessagesUtils::sendTo($player, 'shop.txnBusy'); return null; }
        self::$locks[$name] = true;

        try {
            yield from Main::getInstance()->getEconomyManager()->subtract($player, $total);

            Utils::giveStacked($player->getInventory(), $base, $count);

            return $total;
        } catch (Throwable $e) {
            MessagesUtils::sendTo($player, 'shop.txnFailed', ['{ERR}' => $e->getMessage()]);
            Main::getInstance()->getLogger()->logException($e);
        } finally {
            unset(self::$locks[$name]);
        }
    }

    public static function sell(Player $player, ShopItem $shopItem, int $count): Generator
    {
        $base = $shopItem->getItem();
        $need = $base->getCount() * $count;
        if (Utils::countInInventory($player->getInventory(), $base) < $need) {
            MessagesUtils::sendTo($player, 'shop.notEnoughItems', ['{NEEDED}' => (string)$need]);
            return null;
        }

        $name = strtolower($player->getName());
        if (isset(self::$locks[$name])) {
            MessagesUtils::sendTo($player, 'shop.txnBusy');
            return null;
        }
        self::$locks[$name] = true;

        $unit  = $shopItem->getSellPrice();
        $total = $unit * $count;

        try {
            Utils::takeStacked($player->getInventory(), $base, $count);

            yield from Main::getInstance()->getEconomyManager()->add($player, $total);

            return $total;
        } catch (Throwable $e) {
            MessagesUtils::sendTo($player, 'shop.txnFailed', ['{ERR}' => $e->getMessage()]);
            Main::getInstance()->getLogger()->logException($e);
            try {Utils::giveStacked($player->getInventory(), $base, $count); } catch (Throwable) {}
        } finally {
            unset(self::$locks[$name]);
        }
    }

}