<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberShiftSubCommand extends WSubCommand {

    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("action", false)); // start, end, status
        $this->registerArgument(1, new RawStringArgument("store", false));
        $this->registerArgument(2, new RawStringArgument("notes", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $action = strtolower($this->getArgument($args, "action"));
        $storeId = $this->getArgument($args, "store");
        $notes = $this->getArgument($args, "notes") ?? "";

        switch ($action) {
            case "start":
                $this->startShift($sender, $storeId, $notes);
                break;
            case "end":
                $this->endShift($sender, $storeId);
                break;
            case "status":
                $this->showShiftStatus($sender, $storeId);
                break;
            default:
                $sender->sendMessage(TextFormat::RED . "Invalid action. Use: start, end, or status");
                break;
        }
    }

    private function startShift(Player $player, string $storeId, string $notes): void {
        Await::g2c(
            $this->main->getStoreMemberManager()->startShift(
                $player->getUniqueId()->toString(),
                $storeId,
                $notes
            ),
            function ($shift) use ($player, $storeId) {
                if ($shift) {
                    $player->sendMessage(TextFormat::GREEN . "Shift started successfully in store {$storeId}!");
                    $player->sendMessage(TextFormat::YELLOW . "Remember to end your shift when you're done working.");
                } else {
                    $player->sendMessage(TextFormat::RED . "Failed to start shift. You may already have an active shift.");
                }
            },
            function (\Throwable $error) use ($player) {
                $player->sendMessage(TextFormat::RED . "Error starting shift: " . $error->getMessage());
            }
        );
    }

    private function endShift(Player $player, string $storeId): void {
        Await::g2c(
            $this->main->getStoreMemberManager()->endShift(
                $player->getUniqueId()->toString(),
                $storeId
            ),
            function ($success) use ($player, $storeId) {
                if ($success) {
                    $player->sendMessage(TextFormat::GREEN . "Shift ended successfully in store {$storeId}!");
                    $player->sendMessage(TextFormat::YELLOW . "Thank you for your work today.");
                } else {
                    $player->sendMessage(TextFormat::RED . "Failed to end shift. You may not have an active shift.");
                }
            },
            function (\Throwable $error) use ($player) {
                $player->sendMessage(TextFormat::RED . "Error ending shift: " . $error->getMessage());
            }
        );
    }

    private function showShiftStatus(Player $player, string $storeId): void {
        Await::g2c(
            $this->main->getStoreMemberManager()->getMember($player->getUniqueId()->toString(), $storeId),
            function ($member) use ($player, $storeId) {
                if (!$member) {
                    $player->sendMessage(TextFormat::RED . "You are not a member of store {$storeId}.");
                    return;
                }

                $player->sendMessage(TextFormat::GOLD . "=== Shift Status ===");
                
                if ($member->isOnShift()) {
                    $duration = $member->getCurrentShiftDuration();
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60);
                    
                    $player->sendMessage(TextFormat::GREEN . "Status: ON SHIFT");
                    $player->sendMessage(TextFormat::YELLOW . "Duration: " . sprintf('%02d:%02d', $hours, $minutes));
                    $player->sendMessage(TextFormat::YELLOW . "Started: " . date('H:i:s', $member->getLastShiftStart()));
                } else {
                    $player->sendMessage(TextFormat::RED . "Status: OFF SHIFT");
                    
                    if ($member->getLastShiftEnd()) {
                        $player->sendMessage(TextFormat::GRAY . "Last shift ended: " . date('Y-m-d H:i:s', $member->getLastShiftEnd()));
                    }
                }
            },
            function (\Throwable $error) use ($player) {
                $player->sendMessage(TextFormat::RED . "Error checking shift status: " . $error->getMessage());
            }
        );
    }
}