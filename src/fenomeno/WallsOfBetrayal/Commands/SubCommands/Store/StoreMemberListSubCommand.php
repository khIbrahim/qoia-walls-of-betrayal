<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Store;

use fenomeno\WallsOfBetrayal\Commands\WSubCommand;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\IntegerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StoreMemberListSubCommand extends WSubCommand {

    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("store", false));
        $this->registerArgument(1, new IntegerArgument("page", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);

        $storeId = $this->getArgument($args, "store");
        $page = $this->getArgument($args, "page") ?? 1;

        Await::g2c(
            $this->main->getStoreMemberManager()->getMember($sender->getUniqueId()->toString(), $storeId),
            function ($member) use ($sender, $storeId, $page) {
                if (!$member) {
                    $sender->sendMessage(TextFormat::RED . "You are not a member of this store.");
                    return;
                }

                Await::g2c(
                    $this->main->getStoreMemberManager()->getStoreMembers($storeId, $page, 10),
                    function (array $members) use ($sender, $storeId, $page) {
                        if (empty($members)) {
                            $sender->sendMessage(TextFormat::YELLOW . "No members found for store {$storeId}");
                            return;
                        }

                        $sender->sendMessage(TextFormat::GOLD . "=== Store Members ({$storeId}) - Page {$page} ===");
                        
                        foreach ($members as $storeMember) {
                            $statusColor = $storeMember->isActive() ? TextFormat::GREEN : TextFormat::RED;
                            $status = $storeMember->isActive() ? "Active" : "Inactive";
                            $shiftStatus = $storeMember->isOnShift() ? TextFormat::BLUE . " [ON SHIFT]" : "";
                            
                            $sender->sendMessage(
                                TextFormat::YELLOW . "• " . TextFormat::WHITE . $storeMember->getUsername() . 
                                TextFormat::GRAY . " (" . $storeMember->getRole()->getDisplayName() . ") " .
                                $statusColor . $status . $shiftStatus
                            );
                            
                            $sender->sendMessage(
                                TextFormat::GRAY . "  Sales: $" . number_format($storeMember->getTotalSales(), 2) .
                                " | Commission: $" . number_format($storeMember->getTotalCommission(), 2)
                            );
                        }

                        $sender->sendMessage(TextFormat::GRAY . "Use /storemember list {$storeId} " . ($page + 1) . " for next page");
                    },
                    function (\Throwable $error) use ($sender) {
                        $sender->sendMessage(TextFormat::RED . "Error loading members: " . $error->getMessage());
                    }
                );
            },
            function (\Throwable $error) use ($sender) {
                $sender->sendMessage(TextFormat::RED . "Error checking membership: " . $error->getMessage());
            }
        );
    }
}