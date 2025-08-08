<?php

namespace fenomeno\WallsOfBetrayal\Inventory;

use fenomeno\WallsOfBetrayal\Config\InventoriesConfig;
use fenomeno\WallsOfBetrayal\DTO\InventoryDTO;
use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityInterface;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\item\Item;
use pocketmine\player\Player;

class AbilitiesInventory extends WInventory
{

    public function __construct(protected readonly Player $player){parent::__construct();}

    protected function getInventoryDTO(): InventoryDTO
    {
        return InventoriesConfig::getInventoryDTO(InventoriesConfig::ABILITIES_INVENTORY);
    }

    protected function onClick(Player $player, Item $item): bool
    {
        $player->removeCurrentWindow();
        $player->sendMessage('hello');
        return true;
    }

    protected function placeHolders(): array
    {
        $session = Session::get($this->player);

        return [
            '{ABILITIES}' => !empty($session->getAbilities()) ? implode(', ', array_map(fn(string $ability) => "§7[$ability]", $session->getAbilities())) : '§cNO ABILITIES',
            '{UNLOCKED}'  => count($session->getAbilities()),
            '{LOCKED}'    => count(array_filter(Main::getInstance()->getAbilityManager()->getAllAbilities(), fn(AbilityInterface $ability) => ! in_array($ability->getId(), $session->getAbilities())))
        ];
    }
}