<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\InvMenuTypeIds;
use fenomeno\WallsOfBetrayal\Main;
use InvalidArgumentException;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Utils
{

    public static function getInvMenuSize(int $size): string {
        // plus strict et prévisible
        return match ($size) {
            5   => InvMenuTypeIds::TYPE_HOPPER,
            9,18,27 => InvMenuTypeIds::TYPE_CHEST,
            36,45,54 => InvMenuTypeIds::TYPE_DOUBLE_CHEST,
            default => throw new InvalidArgumentException("Invalid inventory size: $size (allowed: 5,9,18,27,36,45,54)")
        };
    }

    public static function parseSlotSpec(int|string|array $spec): array {
        if (is_int($spec)) return [$spec];
        if (is_array($spec)) return array_values(array_map('intval', $spec));

        $out = [];
        foreach (explode(',', str_replace(' ', '', $spec)) as $token) {
            if ($token === '') continue;
            if (str_contains($token, '..')) {
                [$a, $b] = array_map('intval', explode('..', $token, 2));
                if ($a > $b) [$a, $b] = [$b, $a];
                for ($i = $a; $i <= $b; $i++) $out[] = $i;
            } else {
                $out[] = (int)$token;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Charge un inventaire “front” depuis la config:
     * - `contents` = liste d’objets {slot, item, display-name, description, enchantments, action}
     * - `slot` peut être int | "0..8" | "1,3,5" | "0..8,45..53"
     * - placeholders interpolés via $extraTags
     * Retourne [items, actions]
     *
     * @return array{0: array<int,Item>, 1: array<int,string>}
     */
    public static function loadItems(array $contents, array $extraTags = []): array {
        $items  = [];
        $actions = [];
        $parserItem = StringToItemParser::getInstance();
        $parserEnchant = StringToEnchantmentParser::getInstance();

        foreach ($contents as $i => $entry) {
            try {
                if (isset($entry['slot'])){
                    $slots = self::parseSlotSpec($entry['slot']);
                    if (empty($slots)) continue;
                } else {
                    $slots = self::parseSlotSpec($i);
                }

                $id = (string)($entry['item'] ?? 'paper');
                $base = $parserItem->parse($id) ?? $parserItem->parse('paper');
                if (! $base) continue;

                foreach ($slots as $slot) {
                    $it = clone $base;

                    if (isset($entry['display-name'])) {
                        $it->setCustomName(str_replace(array_keys($extraTags), $extraTags, (string)$entry['display-name']));
                    }
                    if (isset($entry['description'])) {
                        $desc = $entry['description'];
                        if (is_string($desc)) {
                            $desc = explode("\n", $desc);
                        }
                        $desc = array_map(
                            fn($line) => str_replace(array_keys($extraTags), $extraTags, (string)$line),
                            (array)$desc
                        );
                        if ($desc === []) { $desc = [""]; }
                        $it->setLore($desc);
                    }
                    if (isset($entry['enchantments']) && is_array($entry['enchantments'])) {
                        foreach ($entry['enchantments'] as $name => $level) {
                            $e = $parserEnchant->parse((string)$name);
                            if ($e !== null) {
                                $it->addEnchantment(new EnchantmentInstance($e, max(1, (int)$level)));
                            }
                        }
                    }
                    if (isset($entry['count'])) {
                        $c = max(1, min(64, (int)$entry['count']));
                        $it->setCount($c);
                    }

                    if (isset($entry['action'])) {
                        $it->getNamedTag()->setByte((string) $entry['action'], 1);
                        $actions[$slot] = (string)$entry['action'];
                        // $it->getNamedTag()->setString('wob_action', (string)$entry['action']);
                    }

                    $items[$slot] = $it;
                }

            } catch (\Throwable $e) {
                Main::getInstance()->getLogger()->error("loadItems: ".$e->getMessage());
            }
        }

        ksort($items);
        return [$items, $actions];
    }

    public static function loadInventory(array $config, array $extraTags = []): ?InventoryDTO {
        if (empty($config)) return null;

        $name = (string)($config['name'] ?? 'Default Inventory');
        $size = (int)($config['size'] ?? 27);
        $type = self::getInvMenuSize($size);

        [$items, $actions] = self::loadItems($config['contents'] ?? [], $extraTags);
        $targetIndexes = array_values(self::parseSlotSpec($config['targetIndexes'] ?? []));

        return new InventoryDTO(
            name: $name,
            size: $size,
            type: $type,
            items: $items,
            actions: $actions,
            targetIndexes: $targetIndexes,
            meta: (array)($config['meta'] ?? [])
        );
    }

    public static function parseQty(string $raw): ?int {
        if (! preg_match('/^\d+$/', trim($raw))) return null;
        $q = (int)$raw;
        return ($q >= 1 && $q <= 1000) ? $q : null;
    }

    public static function formatCurrency(float $price): string
    {
        [$balance, $decimals] = self::getBalanceAndDecimals($price);

        return Main::getInstance()->getEconomyManager()->getCurrency()->formatter->format($balance);
    }

    public static function countInInventory(Inventory $inv, Item $needle): int {
        $sum = 0;
        foreach ($inv->getContents() as $it) {
            if ($it->equals($needle, false, false)) $sum += $it->getCount();
        }
        return $sum;
    }

    public static function getBalanceAndDecimals(float $total): array
    {
        $amount = explode(".", (string)$total);

        $balance = (int)$amount[0];
        $decimals = (int)($amount[1] ?? 0);
        if ($decimals >= 100) {
            $decimals = 99;
        }

        return [$balance, $decimals];
    }

    public static function canGive(Inventory $inv, Item $base, ?int $qty = null): bool {
        if ($qty === null){
            $qty = 1;
        }
        $required = $base->getCount() * $qty;
        $check = clone $base;
        while ($required > 0) {
            $stack = min($check->getMaxStackSize(), $required);
            $chunk = clone $check;
            $chunk->setCount($stack);
            if (! $inv->canAddItem($chunk)) return false;
            $required -= $stack;
        }
        return true;
    }

    public static function giveStacked(Inventory $inv, Item $base, int $qty): void {
        $required = $base->getCount() * $qty;
        $check = clone $base;
        while ($required > 0) {
            $stack = min($check->getMaxStackSize(), $required);
            $chunk = clone $check;
            $chunk->setCount($stack);
            $inv->addItem($chunk);
            $required -= $stack;
        }
    }

    public static function takeStacked(Inventory $inv, Item $base, int $qty): void {
        $required = $base->getCount() * $qty;
        $probe = clone $base;
        while ($required > 0) {
            $stack = min($probe->getMaxStackSize(), $required);
            $chunk = clone $probe;
            $chunk->setCount($stack);
            $inv->removeItem($chunk);
            $required -= $stack;
        }
    }

    /**
     * @throws \ReflectionException
     */
    public static function getChildPropertiesValues($o): array {
        $reflection = new \ReflectionClass($o);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            // On ne récupère que les propriétés déclarées dans la classe enfant
            if ($property->getDeclaringClass()->getName() === get_class($o)) {
                $property->setAccessible(true); // Nécessaire pour accéder aux protected/private
                $values[] = $property->getValue($o);
            }
        }

        return $values;
    }

    /** 3661 -> "1h 1m", 59 -> "59s" */
    public static function formatDuration(?int $seconds): string
    {
        if($seconds <= 0 || $seconds === null){
            return "None";
        }

        $d = intdiv($seconds, 86400); $seconds %= 86400;
        $h = intdiv($seconds, 3600);  $seconds %= 3600;
        $m = intdiv($seconds, 60);    $s = $seconds % 60;

        $parts = [];
        if ($d) $parts[] = $d . "d";
        if ($h) $parts[] = $h . "h";
        if ($m) $parts[] = $m . "m";
        if ($s && !$d && !$h) $parts[] = $s . "s"; // on n'affiche les secondes que si court
        return implode(" ", $parts);
    }

    public static function canGiveBundle(Inventory $inv, array $items): bool
    {
        foreach ($items as $it) {
            if (!self::canGive($inv, $it)) return false;
        }
        return true;
    }

    public static function tryGiveOrDrop(Player $player, int $slot, Item $item, bool $armor = false): void {
        if (! $armor){
            $inv = $player->getInventory();
        } else {
            $inv = $player->getArmorInventory();
        }

        if ($inv->getItem($slot)->getTypeId() === VanillaItems::AIR()->getTypeId()) {
            $inv->setItem($slot, $item);
            return;
        }

        if (self::canGive($inv, $item)) {
            $inv->addItem($item);
        } else {
            $player->getWorld()->dropItem($player->getPosition(), $item);
        }
    }

    public static function giveItemSet(Player $player, array $items, bool $armor = false): void {
        foreach ($items as $slot => $item) {
            self::tryGiveOrDrop($player, $slot, $item, $armor);
        }
    }

}