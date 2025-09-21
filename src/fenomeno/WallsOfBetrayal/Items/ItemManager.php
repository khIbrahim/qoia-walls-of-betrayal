<?php

namespace fenomeno\WallsOfBetrayal\Items;

use customiesdevs\customies\item\CustomiesItemFactory;

final class ItemManager
{
    public function __construct()
    {
        CustomiesItemFactory::getInstance()->registerItem(static fn() => new Items\Bandage(), Items\Bandage::IDENTIFIER);
        CustomiesItemFactory::getInstance()->registerItem(static fn() => new Items\DivineRestorationPotion(), Items\DivineRestorationPotion::IDENTIFIER);

        WobItems::getAll();
    }
}