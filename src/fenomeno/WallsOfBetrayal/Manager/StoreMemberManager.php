<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Class\Store\StoreMemberShift;
use fenomeno\WallsOfBetrayal\Class\Store\StoreTransaction;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\StoreMemberRepositoryInterface;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use Generator;

final class StoreMemberManager {

    private array $memberCache = [];
    private array $activeShifts = [];

    public function __construct(
        private readonly Main $main,
        private readonly StoreMemberRepositoryInterface $repository
    ) {}

    /**
     * Register a new store member
     */
    public function hireMember(
        string $uuid,
        string $username,
        string $storeId,
        StoreMemberRole $role,
        float $salary = 0.0,
        float $commissionRate = 0.0,
        array $additionalPermissions = []
    ): Await {
        return Await::f2c(function() use ($uuid, $username, $storeId, $role, $salary, $commissionRate, $additionalPermissions): Generator {
            // Validate salary and commission rate are within role limits
            $this->validateSalaryAndCommission($role, $salary, $commissionRate);

            $member = yield from $this->repository->createMember(
                $uuid,
                $username,
                $storeId,
                $role,
                $additionalPermissions,
                $salary,
                $commissionRate
            );

            if ($member) {
                $this->memberCache[$uuid . ':' . $storeId] = $member;
                $this->main->getLogger()->info("§aStore Member hired: {$username} as {$role->getDisplayName()} in store {$storeId}");
            }

            return $member;
        });
    }

    /**
     * Fire a store member
     */
    public function fireMember(string $uuid, string $storeId): Await {
        return Await::f2c(function() use ($uuid, $storeId): Generator {
            $member = yield from $this->repository->getMember($uuid, $storeId);
            
            if (!$member) {
                throw new \InvalidArgumentException("Member not found");
            }

            // End any active shift first
            $activeShift = yield from $this->repository->getActiveShift($uuid, $storeId);
            if ($activeShift) {
                yield from $this->endShift($uuid, $storeId);
            }

            $deletedMember = yield from $this->repository->deleteMember($uuid, $storeId);
            
            unset($this->memberCache[$uuid . ':' . $storeId]);
            unset($this->activeShifts[$uuid . ':' . $storeId]);

            $this->main->getLogger()->info("§cStore Member fired: {$member->getUsername()} from store {$storeId}");

            return $deletedMember;
        });
    }

    /**
     * Get a store member
     */
    public function getMember(string $uuid, string $storeId): Await {
        return Await::f2c(function() use ($uuid, $storeId): Generator {
            $cacheKey = $uuid . ':' . $storeId;
            
            if (isset($this->memberCache[$cacheKey])) {
                return $this->memberCache[$cacheKey];
            }

            $member = yield from $this->repository->getMember($uuid, $storeId);
            
            if ($member) {
                $this->memberCache[$cacheKey] = $member;
            }

            return $member;
        });
    }

    /**
     * Promote or demote a member
     */
    public function updateMemberRole(
        string $uuid,
        string $storeId,
        StoreMemberRole $newRole,
        array $additionalPermissions = []
    ): Await {
        return Await::f2c(function() use ($uuid, $storeId, $newRole, $additionalPermissions): Generator {
            $member = yield from $this->repository->updateMemberRole($uuid, $storeId, $newRole, $additionalPermissions);
            
            if ($member) {
                $this->memberCache[$uuid . ':' . $storeId] = $member;
                $this->main->getLogger()->info("§eMember role updated: {$member->getUsername()} is now {$newRole->getDisplayName()}");
            }

            return $member;
        });
    }

    /**
     * Update member salary and commission
     */
    public function updateMemberCompensation(
        string $uuid,
        string $storeId,
        float $salary,
        float $commissionRate
    ): Await {
        return Await::f2c(function() use ($uuid, $storeId, $salary, $commissionRate): Generator {
            $member = yield from $this->getMember($uuid, $storeId);
            
            if (!$member) {
                throw new \InvalidArgumentException("Member not found");
            }

            $this->validateSalaryAndCommission($member->getRole(), $salary, $commissionRate);

            $updatedMember = yield from $this->repository->updateMemberSalary($uuid, $storeId, $salary, $commissionRate);
            
            if ($updatedMember) {
                $this->memberCache[$uuid . ':' . $storeId] = $updatedMember;
            }

            return $updatedMember;
        });
    }

    /**
     * Start a member's shift
     */
    public function startShift(string $uuid, string $storeId, string $notes = ''): Await {
        return Await::f2c(function() use ($uuid, $storeId, $notes): Generator {
            $member = yield from $this->getMember($uuid, $storeId);
            
            if (!$member) {
                throw new \InvalidArgumentException("Member not found");
            }

            if (!$member->isActive()) {
                throw new \InvalidArgumentException("Member is not active");
            }

            // Check if member already has an active shift
            $activeShift = yield from $this->repository->getActiveShift($uuid, $storeId);
            if ($activeShift) {
                throw new \InvalidArgumentException("Member already has an active shift");
            }

            $shift = yield from $this->repository->startShift($uuid, $storeId, time(), $notes);
            
            if ($shift) {
                $this->activeShifts[$uuid . ':' . $storeId] = $shift;
                $this->main->getLogger()->info("§aShift started: {$member->getUsername()} in store {$storeId}");
            }

            return $shift;
        });
    }

    /**
     * End a member's shift
     */
    public function endShift(
        string $uuid,
        string $storeId,
        int $breakDuration = 0,
        float $salesDuringShift = 0.0,
        float $commissionEarned = 0.0
    ): Await {
        return Await::f2c(function() use ($uuid, $storeId, $breakDuration, $salesDuringShift, $commissionEarned): Generator {
            $activeShift = yield from $this->repository->getActiveShift($uuid, $storeId);
            
            if (!$activeShift) {
                throw new \InvalidArgumentException("No active shift found for member");
            }

            $success = yield from $this->repository->endShift(
                $activeShift->getId(),
                time(),
                $breakDuration,
                $salesDuringShift,
                $commissionEarned
            );

            if ($success) {
                unset($this->activeShifts[$uuid . ':' . $storeId]);
                
                // Update member's shift end time
                yield from $this->repository->endMemberShift($uuid, $storeId, time());
                
                $this->main->getLogger()->info("§cShift ended: {$uuid} in store {$storeId}");
            }

            return $success;
        });
    }

    /**
     * Process a sale transaction
     */
    public function processSale(
        string $memberUuid,
        string $storeId,
        float $totalAmount,
        array $itemsSold,
        string $paymentMethod = 'cash',
        ?string $customerUuid = null,
        string $notes = ''
    ): Await {
        return Await::f2c(function() use (
            $memberUuid, $storeId, $totalAmount, $itemsSold,
            $paymentMethod, $customerUuid, $notes
        ): Generator {
            $member = yield from $this->getMember($memberUuid, $storeId);
            
            if (!$member) {
                throw new \InvalidArgumentException("Member not found");
            }

            if (!$member->canProcessSales()) {
                throw new \InvalidArgumentException("Member cannot process sales");
            }

            // Calculate commission
            $commissionAmount = $totalAmount * ($member->getCommissionRate() / 100);

            // Get active shift
            $activeShift = yield from $this->repository->getActiveShift($memberUuid, $storeId);

            // Create transaction
            $transactionUuid = StoreTransaction::generateUuid();
            $transaction = yield from $this->repository->createTransaction(
                $transactionUuid,
                $storeId,
                $memberUuid,
                $customerUuid,
                'sale',
                $totalAmount,
                $commissionAmount,
                $itemsSold,
                $paymentMethod,
                $activeShift?->getId(),
                $notes
            );

            // Update member's sales and commission
            yield from $this->repository->addMemberSale($memberUuid, $storeId, $totalAmount, $commissionAmount);

            // Update active shift if exists
            if ($activeShift) {
                yield from $this->repository->updateShiftSales($activeShift->getId(), $totalAmount, $commissionAmount);
            }

            // Clear member cache to get updated data
            unset($this->memberCache[$memberUuid . ':' . $storeId]);

            $this->main->getLogger()->info("§aSale processed: ${$totalAmount} by {$member->getUsername()} in store {$storeId}");

            return $transaction;
        });
    }

    /**
     * Process a refund
     */
    public function processRefund(
        string $memberUuid,
        string $storeId,
        string $originalTransactionUuid,
        float $refundAmount,
        string $reason = ''
    ): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $originalTransactionUuid, $refundAmount, $reason): Generator {
            $member = yield from $this->getMember($memberUuid, $storeId);
            
            if (!$member) {
                throw new \InvalidArgumentException("Member not found");
            }

            if (!$member->hasPermission('store.sales.refund')) {
                throw new \InvalidArgumentException("Member cannot process refunds");
            }

            // Get original transaction
            $originalTransaction = yield from $this->repository->getTransaction($originalTransactionUuid);
            
            if (!$originalTransaction) {
                throw new \InvalidArgumentException("Original transaction not found");
            }

            if ($originalTransaction->getStoreId() !== $storeId) {
                throw new \InvalidArgumentException("Transaction does not belong to this store");
            }

            if ($refundAmount > $originalTransaction->getTotalAmount()) {
                throw new \InvalidArgumentException("Refund amount cannot exceed original transaction amount");
            }

            // Calculate commission refund
            $commissionRefund = $refundAmount * ($member->getCommissionRate() / 100);

            // Create refund transaction
            $refundUuid = StoreTransaction::generateUuid();
            $refundTransaction = yield from $this->repository->createTransaction(
                $refundUuid,
                $storeId,
                $memberUuid,
                $originalTransaction->getCustomerUuid(),
                'refund',
                $refundAmount,
                -$commissionRefund, // Negative commission for refund
                $originalTransaction->getItemsSold(),
                $originalTransaction->getPaymentMethod(),
                null,
                "Refund for {$originalTransactionUuid}: {$reason}"
            );

            // Update member's sales (subtract refund)
            yield from $this->repository->addMemberSale($memberUuid, $storeId, -$refundAmount, -$commissionRefund);

            // Clear member cache
            unset($this->memberCache[$memberUuid . ':' . $storeId]);

            $this->main->getLogger()->info("§eRefund processed: ${$refundAmount} by {$member->getUsername()} in store {$storeId}");

            return $refundTransaction;
        });
    }

    /**
     * Get store members with pagination
     */
    public function getStoreMembers(string $storeId, int $page = 1, int $perPage = 20): Await {
        return Await::f2c(function() use ($storeId, $page, $perPage): Generator {
            $offset = ($page - 1) * $perPage;
            return yield from $this->repository->getStoreMembers($storeId, $perPage, $offset);
        });
    }

    /**
     * Get active store members
     */
    public function getActiveStoreMembers(string $storeId): Await {
        return $this->repository->getActiveStoreMembers($storeId);
    }

    /**
     * Search members
     */
    public function searchMembers(string $storeId, string $searchTerm, int $page = 1, int $perPage = 20): Await {
        return Await::f2c(function() use ($storeId, $searchTerm, $page, $perPage): Generator {
            $offset = ($page - 1) * $perPage;
            return yield from $this->repository->searchMembers($storeId, $searchTerm, $perPage, $offset);
        });
    }

    /**
     * Get store statistics
     */
    public function getStoreStats(string $storeId): Await {
        return $this->repository->getStoreStats($storeId);
    }

    /**
     * Get member sales stats for a period
     */
    public function getMemberStats(string $memberUuid, string $storeId, int $days = 30): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $days): Generator {
            $startDate = time() - ($days * 24 * 60 * 60);
            $endDate = time();

            $salesStats = yield from $this->repository->getMemberSalesStats($memberUuid, $storeId, $startDate, $endDate);
            $shiftStats = yield from $this->repository->getShiftStats($memberUuid, $storeId, $startDate, $endDate);

            return [
                'sales' => $salesStats,
                'shifts' => $shiftStats,
                'period_days' => $days
            ];
        });
    }

    /**
     * Check if a player has permission to manage a store
     */
    public function canManageStore(Player $player, string $storeId): Await {
        return Await::f2c(function() use ($player, $storeId): Generator {
            $member = yield from $this->getMember($player->getUniqueId()->toString(), $storeId);
            return $member && $member->canManageStore();
        });
    }

    /**
     * Validate salary and commission rates
     */
    private function validateSalaryAndCommission(StoreMemberRole $role, float $salary, float $commissionRate): void {
        $salaryRange = $role->getSalaryRange();
        $commissionRange = $role->getCommissionRange();

        if ($salary < $salaryRange['min'] || $salary > $salaryRange['max']) {
            throw new \InvalidArgumentException(
                "Salary must be between {$salaryRange['min']} and {$salaryRange['max']} for role {$role->getDisplayName()}"
            );
        }

        if ($commissionRate < $commissionRange['min'] || $commissionRate > $commissionRange['max']) {
            throw new \InvalidArgumentException(
                "Commission rate must be between {$commissionRange['min']}% and {$commissionRange['max']}% for role {$role->getDisplayName()}"
            );
        }
    }

    /**
     * Clear member cache
     */
    public function clearMemberCache(string $uuid, string $storeId): void {
        unset($this->memberCache[$uuid . ':' . $storeId]);
    }

    /**
     * Get cached member if available
     */
    public function getCachedMember(string $uuid, string $storeId): ?StoreMember {
        return $this->memberCache[$uuid . ':' . $storeId] ?? null;
    }

    /**
     * Check if member is on shift
     */
    public function isOnShift(string $uuid, string $storeId): bool {
        return isset($this->activeShifts[$uuid . ':' . $storeId]);
    }

    /**
     * Send notification to store managers
     */
    private function notifyStoreManagers(string $storeId, string $message): void {
        // This could be expanded to send actual notifications
        $this->main->getLogger()->info("Store {$storeId}: {$message}");
    }
}