<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use fenomeno\WallsOfBetrayal\Game\Handlers\AbilityUseHandler;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\item\Item;
use pocketmine\player\Player;

class AbilitiesInventory extends WInventory
{

    public function __construct(protected readonly Player $player){parent::__construct();}

    protected function getInventoryDTO(): InventoryDTO
    {
        $inv = clone InventoriesConfig::getInventoryDTO(InventoriesConfig::ABILITIES_INVENTORY);

        $abilities = array_values(Main::getInstance()->getAbilityManager()->getPlayerAbilities($this->player));
        foreach ($inv->targetIndexes as $i => $index){
            /** @var AbilityInterface|null $ability */
            $ability = $abilities[$i] ?? null;
            if(! $ability){
                continue;
            }

            $item = clone $ability->getIcon($this->player);
            $item->getNamedTag()->setString(AbilityInterface::ABILITY_TAG, $ability->getId());

            $inv->items[$index] = $item;
        }

        return $inv;
    }

    protected function onClickLegacy(Player $player, Item $item): bool
    {
        $player->removeCurrentWindow();

        if (! $item->getNamedTag()->getTag(AbilityInterface::ABILITY_TAG)){
            return true;
        }

        AbilityUseHandler::giveItem($player, $item->getNamedTag()->getString(AbilityInterface::ABILITY_TAG));
        return true;
    }

    protected function placeholders(): array
    {
        $session = Session::get($this->player);

        return [
            '{ABILITIES}' => !empty($session->getAbilities()) ? implode(', ', array_map(fn(string $ability) => "§7[$ability]", $session->getAbilities())) : '§cNO ABILITIES',
            '{UNLOCKED}'  => count(Main::getInstance()->getAbilityManager()->getUnlockedAbilities($this->player)),
            '{LOCKED}'    => count(Main::getInstance()->getAbilityManager()->getLockedAbilities($this->player))
        ];
    }
}