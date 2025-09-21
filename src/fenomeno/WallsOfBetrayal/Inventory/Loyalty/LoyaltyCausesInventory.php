<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Loyalty;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Inventory\Traits\InventoryPaginatorTrait;
use fenomeno\WallsOfBetrayal\Inventory\Types\PageableInventory;
use fenomeno\WallsOfBetrayal\Inventory\WInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class LoyaltyCausesInventory extends WInventory implements PageableInventory
{
    use InventoryPaginatorTrait;

    protected string $filter;
    protected string $sortMode;

    public const FILTER_ALL       = 'all';
    public const FILTER_POSITIVE  = 'positive';
    public const FILTER_NEGATIVE  = 'negative';
    public const FILTER_SORT_ASC  = 'asc';
    public const FILTER_SORT_DESC = 'desc';

    protected const DEFAULT_FILTER    = self::FILTER_ALL;
    protected const DEFAULT_SORT_MODE = self::FILTER_SORT_DESC;
    public const ALLOWED_FILTERS      = [self::FILTER_ALL, self::FILTER_POSITIVE, self::FILTER_NEGATIVE];
    public const ALLOWED_SORT_FILTERS = [self::FILTER_SORT_ASC, self::FILTER_SORT_DESC];
    public const DEFAULT_PAGE = 0;

    public function __construct(string $filter = self::DEFAULT_FILTER, string $sortMode = self::DEFAULT_SORT_MODE, int $page = self::DEFAULT_PAGE, ?int $batch = null)
    {
        $this->filter   = in_array($filter, self::ALLOWED_FILTERS, true) ? $filter : self::DEFAULT_FILTER;
        $this->sortMode = $sortMode === self::FILTER_SORT_ASC ? self::FILTER_SORT_ASC : self::FILTER_SORT_DESC;
        $this->setPage($page);
        if ($batch !== null) $this->setBatch($batch);
        parent::__construct();
    }

    public function getFilter(): string { return $this->filter; }
    public function getSortMode(): string { return $this->sortMode; }

    protected function getInventoryDTO(): InventoryDTO
    {
        $dto = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::LOYALTY_CAUSES_INVENTORY);

        if ($this->getBatch() <= 0) {
            $this->setBatch(max(1, count($dto->targetIndexes)));
        }

        $causes = LoyaltyCause::cases();

        if ($this->filter !== self::FILTER_ALL) {
            $filter = $this->filter;
            $causes = array_values(array_filter($causes, static function(LoyaltyCause $c) use ($filter): bool {
                return $filter === self::FILTER_POSITIVE ? $c->isPositive() : (! $c->isPositive());
            }));
        }

        usort($causes, function(LoyaltyCause $a, LoyaltyCause $b): int {
            $manager = Main::getInstance()->getLoyaltyManager();
            $da = $manager->getCauseDelta($a);
            $db = $manager->getCauseDelta($b);
            return $this->sortMode === self::FILTER_SORT_ASC ? $da <=> $db : $db <=> $da;
        });

        $total  = count($causes);
        $this->clampPage($total);
        $offset = $this->getPage() * $this->getBatch();
        $slice  = array_slice($causes, $offset, $this->getBatch());

        $manager = Main::getInstance()->getLoyaltyManager();

        foreach ($dto->targetIndexes as $i => $slot){
            $cause = $slice[$i] ?? null;
            if (! $cause) continue;

            $base  = $manager->getCauseDisplayItem($cause);
            $delta = $manager->getCauseDelta($cause);
            $color = $delta > 0 ? "§a+" : ($delta < 0 ? "§c" : "§7");
            $sign  = $delta > 0 ? '+' : '';
            $base->setCustomName("§r" . ($delta > 0 ? '§a' : ($delta < 0 ? '§c' : '§7')) . $manager->getCauseDisplayName($cause));
            $lore = [
                "§r§7Name: §f" . $cause->getDisplayName(),
                "§r§7Description: §f" . $cause->getDescription(),
                "§r§7Delta: " . $color . $sign . $delta,
                "§r§7Type: " . ($cause->isPositive() ? '§aPositive' : '§cNegative'),
                "§r§8────────────────────",
            ];
            $desc = trim($manager->getCauseDescription($cause));
            if ($desc !== '') {
                foreach (explode("\n", wordwrap($desc, 40)) as $line){
                    $lore[] = "§7" . $line;
                }
            }
            $lore[] = "§r§8────────────────────";
            $lore[] = "§r§eClick filters below to refine";
            $base->setLore($lore);

            $dto->items[$slot] = $base;
        }

        return $dto;
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        return true;
    }

    protected function placeholders(): array
    {
        return [
            ExtraTags::PACK_SIZE   => (string)($this->getPage() + 1),
            ExtraTags::TOTAL_PAGES => (string)$this->getTotalPages(count(LoyaltyCause::cases())),
            ExtraTags::SORT_MODE   => strtoupper($this->sortMode),
        ];
    }
}

