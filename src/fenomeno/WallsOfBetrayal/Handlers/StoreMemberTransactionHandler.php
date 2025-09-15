<?php

namespace fenomeno\WallsOfBetrayal\Handlers;

use fenomeno\WallsOfBetrayal\Class\Shop\ShopItem;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;

class StoreMemberTransactionHandler
{
    /**
     * Track a shop transaction for a store member
     */
    public static function trackShopTransaction(Player $player, ShopItem $shopItem, int $count, float $totalAmount, string $transactionType): Generator
    {
        $main = Main::getInstance();
        
        // Check if player is a store member
        $member = yield from $main->getStoreMemberManager()->getMemberByPlayer($player);
        if (!$member || !$member->isActive()) {
            return; // Not a store member or not active, no tracking needed
        }

        try {
            // Update member statistics
            $member->addSales($totalAmount);
            $member->incrementTransactions();
            
            // Update in database
            yield from $main->getStoreMemberManager()->updateMember($member);
            
            // Update active session if exists
            $activeSession = yield from Await::promise(
                $main->getDatabaseManager()->getStoreMemberRepository()->getActiveSession($member->getUuid())
            );
            
            if ($activeSession) {
                $activeSession->addSale($totalAmount);
                $activeSession->logActivity(
                    'shop_transaction',
                    "{$transactionType}: {$shopItem->getDisplayName()} x{$count} = {$totalAmount}€"
                );
            }
            
            // Log the transaction
            $main->getLogger()->info(
                "Store member {$player->getName()} made a {$transactionType}: {$shopItem->getDisplayName()} x{$count} = {$totalAmount}€"
            );
            
        } catch (\Throwable $e) {
            $main->getLogger()->error("Failed to track store member transaction: " . $e->getMessage());
            $main->getLogger()->logException($e);
        }
    }

    /**
     * Track a buy transaction
     */
    public static function trackBuyTransaction(Player $player, ShopItem $shopItem, int $count, float $totalAmount): Generator
    {
        yield from self::trackShopTransaction($player, $shopItem, $count, $totalAmount, 'Achat');
    }

    /**
     * Track a sell transaction
     */
    public static function trackSellTransaction(Player $player, ShopItem $shopItem, int $count, float $totalAmount): Generator
    {
        yield from self::trackShopTransaction($player, $shopItem, $count, $totalAmount, 'Vente');
    }

    /**
     * Track custom transaction (manual sale, service, etc.)
     */
    public static function trackCustomTransaction(Player $player, string $description, float $amount): Generator
    {
        $main = Main::getInstance();
        
        // Check if player is a store member
        $member = yield from $main->getStoreMemberManager()->getMemberByPlayer($player);
        if (!$member || !$member->isActive()) {
            return; // Not a store member or not active, no tracking needed
        }

        try {
            // Update member statistics
            $member->addSales($amount);
            $member->incrementTransactions();
            
            // Update in database
            yield from $main->getStoreMemberManager()->updateMember($member);
            
            // Update active session if exists
            $activeSession = yield from Await::promise(
                $main->getDatabaseManager()->getStoreMemberRepository()->getActiveSession($member->getUuid())
            );
            
            if ($activeSession) {
                $activeSession->addSale($amount);
                $activeSession->logActivity('custom_transaction', $description);
            }
            
            // Log the transaction
            $main->getLogger()->info(
                "Store member {$player->getName()} made a custom transaction: {$description} = {$amount}€"
            );
            
        } catch (\Throwable $e) {
            $main->getLogger()->error("Failed to track custom store member transaction: " . $e->getMessage());
            $main->getLogger()->logException($e);
        }
    }

    /**
     * Get daily sales report for all active store members
     */
    public static function getDailySalesReport(): Generator
    {
        $main = Main::getInstance();
        
        try {
            $activeSessions = yield from $main->getStoreMemberManager()->getActiveSessions();
            $report = [];
            
            foreach ($activeSessions as $session) {
                $member = yield from $main->getStoreMemberManager()->getMemberByUuid($session->getMemberUuid());
                if ($member) {
                    $report[] = [
                        'member' => $member,
                        'session' => $session,
                        'daily_sales' => $session->getSalesThisSession(),
                        'daily_transactions' => $session->getTransactionsThisSession(),
                        'hours_worked' => $session->getSessionDurationHours()
                    ];
                }
            }
            
            // Sort by daily sales
            usort($report, fn($a, $b) => $b['daily_sales'] <=> $a['daily_sales']);
            
            return $report;
            
        } catch (\Throwable $e) {
            $main->getLogger()->error("Failed to generate daily sales report: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate commission for a store member based on their sales
     */
    public static function calculateCommission(Player $player, float $salesAmount): float
    {
        $main = Main::getInstance();
        
        Await::g2c(
            $main->getStoreMemberManager()->getMemberByPlayer($player),
            function ($member) use (&$commission, $salesAmount) {
                if (!$member) {
                    $commission = 0.0;
                    return;
                }
                
                // Commission rates based on role
                $commissionRate = match ($member->getRole()) {
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::OWNER => 0.50,       // 50%
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::MANAGER => 0.20,     // 20%
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::SUPERVISOR => 0.15,  // 15%
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::CASHIER => 0.10,     // 10%
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::EMPLOYEE => 0.08,    // 8%
                    \fenomeno\WallsOfBetrayal\Enum\StoreMemberRole::TRAINEE => 0.05,     // 5%
                };
                
                $commission = $salesAmount * $commissionRate;
            },
            function (\Throwable $error) use (&$commission) {
                $commission = 0.0;
            }
        );
        
        return $commission ?? 0.0;
    }
}