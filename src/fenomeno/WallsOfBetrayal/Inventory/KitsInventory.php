<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Game\Handlers\KitClaimHandler;
use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Inventory\Traits\InventoryPaginatorTrait;
use fenomeno\WallsOfBetrayal\Inventory\Types\PageableInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class KitsInventory extends WInventory implements PageableInventory
{
    use InventoryPaginatorTrait;

    private ?Kit $currentKit = null;

    public const KIT_TAG          = 'Kit';
    private const ACT_PREVIEW     = 'kit_preview';
    private const ACT_CLAIM       = 'kit_claim';
    private const ACT_BACK        = 'back_to_kits';
    private const ACT_PREV_PAGE   = 'previous_page';
    private const ACT_NEXT_PAGE   = 'next_page';
    private const ACT_CLOSE       = 'close_menu';
    private const BACK_TO_DETAILS = 'back_to_details';

    private array $allKits;

    public function __construct(protected readonly Player $player, int $page = 0)
    {
        $this->allKits = Main::getInstance()->getKitsManager()->getKits() ?? [];
        $this->setPage($page);
        parent::__construct();
    }

    protected function getInventoryDTO(): InventoryDTO
    {
        $inventoryDTO = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::CHOOSE_KIT_INVENTORY);

        if ($this->getBatch() < 0){
            $this->setBatch(max(1, count($inventoryDTO->targetIndexes)));
        }

        $total   = count($this->allKits);
        $this->clampPage($total);

        $offset   = $this->getPage() * $this->getBatch();
        $pageKits = array_values(array_slice($this->allKits, $offset, $this->getBatch()));

        foreach ($inventoryDTO->targetIndexes as $i => $index) {
            $kit = $pageKits[$i] ?? null;
            if (! $kit) {
                continue;
            }

            $inventoryDTO->items[$index] = $kit->getDisplayItemFor($this->player);
        }

        return $inventoryDTO;
    }

    protected function onClick(Player $player, Item $item, int $slot, ?string $action): bool
    {
        if ($action === self::ACT_PREV_PAGE) { $this->previousPage(); return $this->reopenMenu($player); }
        if ($action === self::ACT_NEXT_PAGE) { $this->nextPage();     return $this->reopenMenu($player); }
        if ($action === self::ACT_CLOSE)     { $player->removeCurrentWindow(); return true; }

        if ($action === self::ACT_BACK)   { return $this->reopenMenu($player); }
        if ($action === self::ACT_PREVIEW && $this->currentKit instanceof Kit) {
            $this->openPreview($this->currentKit); return true;
        }
        if ($action === self::ACT_CLAIM && $this->currentKit instanceof Kit) {
            $this->claimKit($player, $this->currentKit); return true;
        }
        if ($action === self::BACK_TO_DETAILS) {
            $this->openDetails($player, $this->currentKit);
            return true;
        }

        if ($item->getNamedTag()->getTag(self::KIT_TAG) !== null) {
            $kitId = $item->getNamedTag()->getString(self::KIT_TAG, '');
            $kit   = Main::getInstance()->getKitsManager()->getKitById($kitId);
            if (!$kit) {
                MessagesUtils::sendTo($player, 'kits.unknown', ['{KIT}' => $kitId]);
                $player->removeCurrentWindow();
                return true;
            }
            $this->currentKit = $kit;
            $this->openDetails($player, $kit);
            return true;
        }

        return true;
    }

    private function reopenMenu(Player $player): bool
    {
        $player->removeCurrentWindow();
        Main::getInstance()->getScheduler()->scheduleDelayedTask(
            new ClosureTask(fn() => (new self($player, $this->getPage()))->send($player)),
            2
        );
        return true;
    }

    private function openDetails(Player $player, Kit $kit): void
    {
        $this->getInventory()->clearAll();

        $dto = clone InventoriesConfig::getInventoryDTO('kit-details');
        $dto->name = str_replace('{KIT_NAME}', $kit->getDisplayName(), $dto->name);

        foreach ($dto->items as $s => $it) {
            $it = clone $it;
            $it->setCustomName(str_replace('{KIT_NAME}', $kit->getDisplayName(), $it->getCustomName()));
            $it->setLore(array_map(fn($l) => $this->replaceDetailsPlaceholders($l, $player, $kit), $it->getLore()));
            $dto->items[$s] = $it;
        }

        $centerSlot = $dto->targetIndexes[0] ?? 13;
        $centerItem = $kit->getDisplayItemFor($player);

        $this->getInventory()->setContents($dto->items);
        $this->getInventory()->setItem($centerSlot, $centerItem);

        $this->dto = $dto;
    }

    private function openPreview(Kit $kit): void
    {
        $this->getInventory()->clearAll();

        $dto = clone InventoriesConfig::getInventoryDTO('kit-preview');
        $dto->name = str_replace('{KIT_NAME}', $kit->getDisplayName(), $dto->name);

        $items = array_values(array_merge(array_values($kit->getInventory()), array_values($kit->getArmor())));
        foreach ($dto->targetIndexes as $i => $targetIndex){
            if (! isset($items[$i])){
                continue;
            }

            $dto->items[$i] = $items[$i];
        }

        $this->dto = $dto;
        $this->getInventory()->setContents($dto->items);
    }

    private function claimKit(Player $player, Kit $kit): void
    {
        KitClaimHandler::claim($player, $kit);

        $player->removeCurrentWindow();
        $this->currentKit = null;
    }

    protected function onClose(Player $player, Inventory $inventory): void
    {
        $this->currentKit = null;
    }

    protected function placeholders(): array
    {
        return [
            '{PAGE}'        => (string) ($this->getPage() + 1),
            '{TOTAL_PAGES}' => (string) $this->getTotalPages(count($this->allKits)),
            '{KIT}'         => (string) ($this->currentKit ?? '')
        ];
    }

    private function replaceDetailsPlaceholders(string $line, Player $player, Kit $kit): string
    {
        return str_replace(
            ['{KIT_NAME}', '{KIT_DESC}', '{KIT_STATUS}', '{KIT_COOLDOWN}', '{KIT_REQ_SUMMARY}'],
            [
                $kit->getDisplayName(),
                $kit->getDescription(),
                $kit->statusTextFor($player),
                Utils::formatDuration($kit->getCooldown()),
                $kit->reqSummary()
            ],
            $line
        );
    }
}