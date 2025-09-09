<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\LoadPlayerInventoriesPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\PlayerInventory\SavePlayerInventoriesPayload;
use Generator;

interface PlayerInventoriesRepositoryInterface extends RepositoryInterface
{

    public const TAG_INVENTORY       = 'PlayerContextInventory';
    public const TAG_ARMOR_INVENTORY = 'PlayerContextArmorInventory';
    public const TAG_OFF_HAND_INV    = 'PlayerContextOffHandInventory';

    public function load(LoadPlayerInventoriesPayload $payload): Generator;

    public function save(SavePlayerInventoriesPayload $payload): Generator;

}