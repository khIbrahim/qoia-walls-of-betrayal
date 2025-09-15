<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Class\Store\StoreMemberShift;
use fenomeno\WallsOfBetrayal\Class\Store\StoreTransaction;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use SOFe\AwaitGenerator\Await;

interface StoreMemberRepositoryInterface extends RepositoryInterface {

    /**
     * Create a new store member
     */
    public function createMember(
        string $uuid,
        string $username,
        string $storeId,
        StoreMemberRole $role,
        array $permissions = [],
        float $salary = 0.0,
        float $commissionRate = 0.0
    ): Await;

    /**
     * Get a store member by UUID and store ID
     */
    public function getMember(string $uuid, string $storeId): Await;

    /**
     * Get all members of a store with pagination
     */
    public function getStoreMembers(string $storeId, int $limit = 20, int $offset = 0): Await;

    /**
     * Get active members of a store
     */
    public function getActiveStoreMembers(string $storeId): Await;

    /**
     * Update member role and permissions
     */
    public function updateMemberRole(string $uuid, string $storeId, StoreMemberRole $role, array $permissions): Await;

    /**
     * Update member salary and commission rate
     */
    public function updateMemberSalary(string $uuid, string $storeId, float $salary, float $commissionRate): Await;

    /**
     * Set member active status
     */
    public function setMemberActivity(string $uuid, string $storeId, bool $isActive): Await;

    /**
     * Add sale to member's record
     */
    public function addMemberSale(string $uuid, string $storeId, float $saleAmount, float $commissionAmount): Await;

    /**
     * Start a member's shift
     */
    public function startMemberShift(string $uuid, string $storeId, int $startTime): Await;

    /**
     * End a member's shift
     */
    public function endMemberShift(string $uuid, string $storeId, int $endTime): Await;

    /**
     * Get top selling members
     */
    public function getTopSellers(string $storeId, int $limit = 10): Await;

    /**
     * Search members by username or role
     */
    public function searchMembers(string $storeId, string $searchTerm, int $limit = 20, int $offset = 0): Await;

    /**
     * Delete a member from the store
     */
    public function deleteMember(string $uuid, string $storeId): Await;

    /**
     * Get store statistics
     */
    public function getStoreStats(string $storeId): Await;

    /**
     * Start a shift for a member
     */
    public function startShift(string $memberUuid, string $storeId, int $shiftStart, string $notes = ''): Await;

    /**
     * End a shift
     */
    public function endShift(int $shiftId, int $shiftEnd, int $breakDuration, float $salesDuringShift, float $commissionEarned): Await;

    /**
     * Get active shift for a member
     */
    public function getActiveShift(string $memberUuid, string $storeId): Await;

    /**
     * Get member shifts with pagination
     */
    public function getMemberShifts(string $memberUuid, string $storeId, int $limit = 20, int $offset = 0): Await;

    /**
     * Get store shifts within date range
     */
    public function getStoreShifts(string $storeId, int $startDate, int $endDate, int $limit = 50, int $offset = 0): Await;

    /**
     * Get shift statistics for a member
     */
    public function getShiftStats(string $memberUuid, string $storeId, int $startDate, int $endDate): Await;

    /**
     * Cancel a shift
     */
    public function cancelShift(int $shiftId): Await;

    /**
     * Update shift sales
     */
    public function updateShiftSales(int $shiftId, float $salesAmount, float $commissionAmount): Await;

    /**
     * Create a new transaction
     */
    public function createTransaction(
        string $transactionUuid,
        string $storeId,
        string $memberUuid,
        ?string $customerUuid,
        string $transactionType,
        float $totalAmount,
        float $commissionAmount,
        array $itemsSold,
        string $paymentMethod,
        ?int $shiftId = null,
        string $notes = ''
    ): Await;

    /**
     * Get a transaction by UUID
     */
    public function getTransaction(string $transactionUuid): Await;

    /**
     * Get member transactions
     */
    public function getMemberTransactions(string $memberUuid, string $storeId, int $limit = 20, int $offset = 0): Await;

    /**
     * Get store transactions within date range
     */
    public function getStoreTransactions(string $storeId, int $startDate, int $endDate, int $limit = 50, int $offset = 0): Await;

    /**
     * Get transactions for a specific shift
     */
    public function getTransactionsByShift(int $shiftId): Await;

    /**
     * Get member sales statistics
     */
    public function getMemberSalesStats(string $memberUuid, string $storeId, int $startDate, int $endDate): Await;

    /**
     * Get store sales statistics
     */
    public function getStoreSalesStats(string $storeId, int $startDate, int $endDate): Await;

    /**
     * Get top selling items
     */
    public function getTopSellingItems(string $storeId, int $startDate, int $endDate, int $limit = 10): Await;

    /**
     * Void a transaction
     */
    public function voidTransaction(string $transactionUuid): Await;
}