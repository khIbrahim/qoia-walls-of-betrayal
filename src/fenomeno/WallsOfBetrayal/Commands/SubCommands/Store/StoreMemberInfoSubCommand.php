<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberInfoSubCommand extends WSubCommand {

    protected function prepare(): void {
        $this->registerArgument(0, new TargetPlayerArgument("player", false));
        $this->registerArgument(1, new RawStringArgument("store", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $targetPlayer = $this->getArgument($args, "player");
        $storeId = $this->getArgument($args, "store");

        if (!$targetPlayer instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Player not found or not online.");
            return;
        }

        Await::g2c(
            $this->main->getStoreMemberManager()->getMember($targetPlayer->getUniqueId()->toString(), $storeId),
            function ($member) use ($sender, $targetPlayer, $storeId) {
                if (!$member) {
                    $sender->sendMessage(TextFormat::RED . "{$targetPlayer->getName()} is not a member of store {$storeId}.");
                    return;
                }

                $sender->sendMessage(TextFormat::GOLD . "=== Member Info: {$targetPlayer->getName()} ===");
                $sender->sendMessage(TextFormat::YELLOW . "Store: " . TextFormat::WHITE . $storeId);
                $sender->sendMessage(TextFormat::YELLOW . "Role: " . TextFormat::WHITE . $member->getRole()->getDisplayName());
                
                $statusColor = $member->isActive() ? TextFormat::GREEN : TextFormat::RED;
                $status = $member->isActive() ? "Active" : "Inactive";
                $sender->sendMessage(TextFormat::YELLOW . "Status: " . $statusColor . $status);
                
                $sender->sendMessage(TextFormat::YELLOW . "Salary: " . TextFormat::WHITE . "$" . number_format($member->getSalary(), 2));
                $sender->sendMessage(TextFormat::YELLOW . "Commission Rate: " . TextFormat::WHITE . $member->getCommissionRate() . "%");
                
                $sender->sendMessage(TextFormat::YELLOW . "Total Sales: " . TextFormat::GREEN . "$" . number_format($member->getTotalSales(), 2));
                $sender->sendMessage(TextFormat::YELLOW . "Total Commission: " . TextFormat::GREEN . "$" . number_format($member->getTotalCommission(), 2));
                
                $sender->sendMessage(TextFormat::YELLOW . "Hire Date: " . TextFormat::WHITE . date('Y-m-d H:i:s', $member->getHireDate()));
                
                if ($member->isOnShift()) {
                    $duration = $member->getCurrentShiftDuration();
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60);
                    $sender->sendMessage(TextFormat::BLUE . "Currently on shift: " . sprintf('%02d:%02d', $hours, $minutes));
                } else {
                    $sender->sendMessage(TextFormat::GRAY . "Not currently on shift");
                }

                // Get recent stats
                Await::g2c(
                    $this->main->getStoreMemberManager()->getMemberStats(
                        $targetPlayer->getUniqueId()->toString(),
                        $storeId,
                        7 // Last 7 days
                    ),
                    function ($stats) use ($sender) {
                        if ($stats && $stats['sales']) {
                            $salesStats = $stats['sales'];
                            $sender->sendMessage(TextFormat::YELLOW . "Last 7 days:");
                            $sender->sendMessage(TextFormat::GRAY . "  Sales: $" . number_format($salesStats['total_sales'] ?? 0, 2));
                            $sender->sendMessage(TextFormat::GRAY . "  Transactions: " . ($salesStats['total_transactions'] ?? 0));
                            $sender->sendMessage(TextFormat::GRAY . "  Avg Sale: $" . number_format($salesStats['avg_sale_amount'] ?? 0, 2));
                        }
                    },
                    function (\Throwable $error) use ($sender) {
                        // Ignore stats error, just don't show them
                    }
                );
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage(TextFormat::RED . "Error loading member info: " . $error->getMessage());
            }
        );
    }
}