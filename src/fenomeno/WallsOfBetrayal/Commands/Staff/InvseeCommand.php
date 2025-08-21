<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Commands\Staff;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenu;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\type\InvMenuTypeIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use Throwable;

class InvseeCommand extends WCommand
{
    private const PLAYER_ARGUMENT = 'player';

    private const SLOT_HELMET   = 0;
    private const SLOT_CHEST    = 1;
    private const SLOT_LEGS     = 2;
    private const SLOT_BOOTS    = 3;
    private const SLOT_INV_START = 9;

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $targetName = (string) $args[self::PLAYER_ARGUMENT];
        $target     = $sender->getServer()->getPlayerExact($targetName);
        if (! $target instanceof Player) {
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND);
            return;
        }

        try {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
            $menu->setName("§6InvSee §7| §f" . $target->getName());

            $inv = $menu->getInventory();

            $separator = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY)->asItem()->setCustomName(" ");
            for ($i = 4; $i <= 8; $i++) {
                $inv->setItem($i, $separator);
            }

            $armor = $target->getArmorInventory();
            $inv->setItem(self::SLOT_HELMET, $armor->getHelmet() ?? VanillaItems::AIR());
            $inv->setItem(self::SLOT_CHEST,  $armor->getChestplate() ?? VanillaItems::AIR());
            $inv->setItem(self::SLOT_LEGS,   $armor->getLeggings() ?? VanillaItems::AIR());
            $inv->setItem(self::SLOT_BOOTS,  $armor->getBoots() ?? VanillaItems::AIR());

            $targetInv = $target->getInventory();
            for ($slot = 0; $slot < 36; $slot++) {
                $menuSlot = self::SLOT_INV_START + $slot;
                $inv->setItem($menuSlot, $targetInv->getItem($slot));
            }

            $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($targetName, $sender, $target, $inv): void {
                try {
                    if (! $target->isOnline()) {
                        MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND);
                        return;
                    }

                    $helmet = $inventory->getItem(self::SLOT_HELMET);
                    $chest  = $inventory->getItem(self::SLOT_CHEST);
                    $legs   = $inventory->getItem(self::SLOT_LEGS);
                    $boots  = $inventory->getItem(self::SLOT_BOOTS);

                    $target->getArmorInventory()->setHelmet($helmet);
                    $target->getArmorInventory()->setChestplate($chest);
                    $target->getArmorInventory()->setLeggings($legs);
                    $target->getArmorInventory()->setBoots($boots);

                    $tInv = $target->getInventory();
                    for ($slot = 0; $slot < 36; $slot++) {
                        $menuSlotItem = $inventory->getItem(self::SLOT_INV_START + $slot);
                        $tInv->setItem($slot, $menuSlotItem);
                    }

                    MessagesUtils::sendTo($sender, MessagesIds::INVSEE_UPDATED, [ExtraTags::PLAYER => $target->getName()]);
                } catch (Throwable $e){
                    MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
                    $this->main->getLogger()->error("Failed to save invsee of $targetName, command used by {$sender->getName()}: " . $e->getMessage());
                    $this->main->getLogger()->logException($e);
                }
            });

            $menu->send($sender);
        } catch (Throwable $e){
            MessagesUtils::sendTo($sender, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            $this->main->getLogger()->error("Failed to open invsee of $targetName, command used by {$sender->getName()}: " . $e->getMessage());
            $this->main->getLogger()->logException($e);
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::INVSEE);
    }
}