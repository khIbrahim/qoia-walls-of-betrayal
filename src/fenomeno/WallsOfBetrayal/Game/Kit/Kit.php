<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Inventory\KitsInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Kit
{
    /** @param KitRequirement[] $requirements */
    public function __construct(
        private readonly string   $id,
        private readonly string   $displayName,
        private readonly string   $description,
        private readonly int      $unlockDay,
        private readonly Item     $item,
        private readonly array    $inv,             // slot => Item
        private readonly array    $armor,           // slot => Item
        private readonly array    $requirements = [],    // je les load après,
        private readonly string   $permission   = DefaultPermissions::ROOT_USER,
        private readonly ?Kingdom $kingdom      = null,
        private readonly int      $cooldown     = 0
    ) {}

    public function getKingdom(): ?Kingdom { return $this->kingdom; }
    public function getDisplayName(): string { return $this->displayName; }
    public function getDescription(): string { return $this->description; }
    public function getItem(): Item { return $this->item; }
    public function getUnlockDay(): int { return $this->unlockDay; }
    public function getCooldown(): int { return $this->cooldown; }

    /** @return Item[] */
    public function getInventory(): array { return $this->inv; }

    /** @return Item[] */
    public function getArmor(): array { return $this->armor; }

    /** @return KitRequirement[] */
    public function getRequirements(): array { return $this->requirements; }
    public function getPermission(): string { return $this->permission; }

    public function getId(): string
    {
        return $this->id;
    }

    public function isRequirementsAchieved(): bool
    {
        if(! $this->hasRequirements()){
            return true;
        }

        foreach ($this->requirements as $requirement) {
            if (! $requirement->isComplete()) {
                return false;
            }
        }
        return true;
    }

    public function getRequirement(int $id): ?KitRequirement
    {
        return $this->requirements[$id] ?? null;
    }

    public function getDisplayItemFor(Player $player): Item
    {
        $item = clone $this->getItem();
        $item->setCustomName("§r" . $this->getDisplayName());

        $lore = [];

        $lore[] = "§r" . $this->getDescription();

        $lore[] = "§8────────────────────────";
        $lore[] = "§7Status: "    . $this->statusTextFor($player);
        $lore[] = "§7Cooldown: "  . Utils::formatDuration($this->getCooldown());
        if ($this->hasRequirements()){
            $lore[] = "§7Req: "       . $this->reqSummary();
        }
        $lore[] = "§8Left: Preview • Right: Claim";

        $item->setLore($lore);
        $item->getNamedTag()->setString(KitsInventory::KIT_TAG, $this->getId());

        return $item;
    }

    public function statusTextFor(Player $player): string
    {
        $ready = true;
        $cause = "";
        if ($this->hasRequirements() && ! $this->isRequirementsAchieved()){
            $cause = "Requirements Not Achieved";
            $ready = false;
        }
        if(Main::getInstance()->getCooldownManager()->isOnCooldown($this->getId(), $player->getName())){
            $cause = "Cooldown: " . Utils::formatDuration(Main::getInstance()->getCooldownManager()->getCooldownRemaining($this->getId(), $player->getName()));
            $ready = false;
        }
        if(! $player->hasPermission($this->getPermission())){
            $cause = "No Permission";
            $ready = false;
        }
        $session = Session::get($player);
        if ($this->hasKingdom() && $session->isLoaded() && $session->getKingdom() !== null && $session->getKingdom()->getId() !== $this->getKingdom()->getId()){
            $cause = "Reserved for kingdom: " . $this->getKingdom()->getDisplayName();
            $ready = false;
        }
        return $ready ? "§a✔ Ready" : "§c✖ Not Ready ($cause)";
    }

    public function reqSummary(): string
    {
        if (! $this->hasRequirements()) return "§7None";

        $parts = [];
        $parts[] = "§r";

        foreach ($this->getRequirements() as $req) {
            $progress = $req->getProgress();
            $required = $req->getAmount();
            $icon = $req->getType() === KitRequirementType::BREAK ? "§7•" : "§7×";
            $target = ucfirst((string) $req->getTarget());

            $parts[] = "§r§8 $icon §f$target §7– §f{$progress}§8/§f$required";
        }

        return "§7" . implode("   \n", $parts);
    }

    public function hasRequirements(): bool
    {
        return ! empty($this->requirements) || $this->requirements !== [];
    }

    public function hasKingdom(): bool
    {
        return $this->kingdom !== null;
    }

}
