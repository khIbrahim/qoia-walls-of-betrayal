<?php

namespace fenomeno\WallsOfBetrayal\Handlers;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Class\Shop\ShopItem;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
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
                '{PRICE}' => (string) $total
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
        $packSize = $shopItem->getPackSize();

        $availableUnits = Utils::countInInventory($player->getInventory(), $base);
        if ($availableUnits <= 0) {
            MessagesUtils::sendTo($player, 'shop.notEnoughItems', [ExtraTags::NEEDED => (string) $packSize]);
            return null;
        }

        $itemsToSell = min($count, $availableUnits);

        $packs = intdiv($itemsToSell, $packSize);
        if ($packs <= 0) {
            MessagesUtils::sendTo($player, 'shop.notEnoughItems', [ExtraTags::NEEDED => (string) $packSize]);
            return null;
        }

        $name = strtolower($player->getName());
        if (isset(self::$locks[$name])) {
            MessagesUtils::sendTo($player, 'shop.txnBusy');
            return null;
        }
        self::$locks[$name] = true;

        $unit  = $shopItem->getSellPrice();
        $total = $unit * $packs;

        try {
            Utils::takeStacked($player->getInventory(), $base, $packs);

            yield from Main::getInstance()->getEconomyManager()->add($player, $total);
            return $total;
        } catch (Throwable $e) {
            MessagesUtils::sendTo($player, 'shop.txnFailed', ['{ERR}' => $e->getMessage()]);
            Main::getInstance()->getLogger()->logException($e);
            try { Utils::giveStacked($player->getInventory(), $base, $packs); } catch (Throwable) {}
        } finally {
            unset(self::$locks[$name]);
        }
        return null;
    }

    public static function sellAll(Player $player): Generator
    {
        $sellable = [];
        foreach ($player->getInventory()->getContents() as $item) {
            if ($item->isNull() || $item->getCount() <= 0) continue;

            $shopItem = Main::getInstance()->getShopManager()->getShopItemByItem($item);
            if ($shopItem === null || $shopItem->getSellPrice() <= 0) continue;

            $sellable[$shopItem->getId()] = $shopItem;
        }

        if (empty($sellable)) {
            return 0;
        }

        $total = 0;
        foreach ($sellable as $shopItem) {
            $availableUnits = Utils::countInInventory($player->getInventory(), $shopItem->getItem());
            if ($availableUnits <= 0) continue;

            $result = yield from self::sell($player, $shopItem, $availableUnits);
            if ($result !== null) {
                $total += $result;
            }
        }

        return $total;
    }

}