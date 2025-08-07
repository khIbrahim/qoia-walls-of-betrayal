<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use pocketmine\item\Item;

class Kit
{
    /** @param KitRequirement[] $requirements */
    public function __construct(
        private readonly string  $id,
        private readonly Kingdom $kingdom,
        private string           $displayName,
        private string           $description,
        private int              $unlockDay,
        private Item             $item,
        private array            $inv,             // slot => Item
        private array            $armor,           // slot => Item
        private array            $requirements = []    // je les load après
    ) {}

    public function getKingdom(): Kingdom { return $this->kingdom; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getDescription(): string { return $this->description; }
    public function getItem(): Item { return $this->item; }
    public function getUnlockDay(): int { return $this->unlockDay; }

    /** @return Item[] */
    public function getInventory(): array { return $this->inv; }

    /** @return Item[] */
    public function getArmor(): array { return $this->armor; }

    /** @return KitRequirement[] */
    public function getRequirements(): array { return $this->requirements; }

    public function getId(): string
    {
        return $this->id;
    }

    public function isRequirementsAchieved(): bool
    {
        foreach ($this->requirements as $requirement) {
            if (! $requirement->isComplete()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Pour plus de clareté au niveau de l'API
     *
     * @return bool
     */
    public function isUnlocked(): bool
    {
        return $this->isRequirementsAchieved();
    }
}
