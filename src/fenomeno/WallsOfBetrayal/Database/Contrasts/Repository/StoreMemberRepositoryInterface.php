<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMember;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMemberSession;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use Generator;
use pocketmine\promise\Promise;

interface StoreMemberRepositoryInterface extends RepositoryInterface
{
    /**
     * Load a store member by UUID
     * 
     * @param string $uuid
     * @return Promise<StoreMember|null>
     */
    public function loadByUuid(string $uuid): Promise;

    /**
     * Load a store member by username
     * 
     * @param string $username
     * @return Promise<StoreMember|null>
     */
    public function loadByUsername(string $username): Promise;

    /**
     * Insert a new store member
     * 
     * @param StoreMember $member
     * @return Generator
     */
    public function insert(StoreMember $member): Generator;

    /**
     * Update an existing store member
     * 
     * @param StoreMember $member
     * @return Generator
     */
    public function update(StoreMember $member): Generator;

    /**
     * Delete a store member
     * 
     * @param string $uuid
     * @return Generator
     */
    public function delete(string $uuid): Generator;

    /**
     * Get all store members
     * 
     * @return Promise<StoreMember[]>
     */
    public function getAll(): Promise;

    /**
     * Get store members by role
     * 
     * @param StoreMemberRole $role
     * @return Promise<StoreMember[]>
     */
    public function getByRole(StoreMemberRole $role): Promise;

    /**
     * Get store members by status
     * 
     * @param StoreMemberStatus $status
     * @return Promise<StoreMember[]>
     */
    public function getByStatus(StoreMemberStatus $status): Promise;

    /**
     * Get store members by store ID
     * 
     * @param string|null $storeId
     * @return Promise<StoreMember[]>
     */
    public function getByStoreId(?string $storeId): Promise;

    /**
     * Update member's last login
     * 
     * @param string $uuid
     * @return Generator
     */
    public function updateLastLogin(string $uuid): Generator;

    /**
     * Update member's work statistics
     * 
     * @param string $uuid
     * @param int $additionalHours
     * @param float $additionalSales
     * @param int $additionalTransactions
     * @return Generator
     */
    public function updateWorkStats(string $uuid, int $additionalHours, float $additionalSales, int $additionalTransactions): Generator;

    /**
     * Get top performers by sales
     * 
     * @param int $limit
     * @return Promise<StoreMember[]>
     */
    public function getTopPerformersBySales(int $limit = 10): Promise;

    /**
     * Get top performers by transactions
     * 
     * @param int $limit
     * @return Promise<StoreMember[]>
     */
    public function getTopPerformersByTransactions(int $limit = 10): Promise;

    /**
     * Start a work session for a member
     * 
     * @param string $memberUuid
     * @return Generator
     */
    public function startSession(string $memberUuid): Generator;

    /**
     * End a work session for a member
     * 
     * @param string $memberUuid
     * @param float $sales
     * @param int $transactions
     * @return Generator
     */
    public function endSession(string $memberUuid, float $sales, int $transactions): Generator;

    /**
     * Get active session for a member
     * 
     * @param string $memberUuid
     * @return Promise<StoreMemberSession|null>
     */
    public function getActiveSession(string $memberUuid): Promise;

    /**
     * Get all active sessions
     * 
     * @return Promise<StoreMemberSession[]>
     */
    public function getAllActiveSessions(): Promise;

    /**
     * Get member work history
     * 
     * @param string $memberUuid
     * @param int $limit
     * @return Promise<StoreMemberSession[]>
     */
    public function getMemberSessionHistory(string $memberUuid, int $limit = 50): Promise;
}