<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Store\Store;
use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberStatus;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Database\StoreDatabase;
use Generator;
use InvalidArgumentException;
use Throwable;

/**
 * Manages store members and their operations for the restaurant POS ecosystem
 * Provides CRUD operations, validation, caching, and business logic
 */
final class StoreMemberManager {

    /** @var array<string, Store> */
    private array $stores = [];
    
    /** @var array<string, StoreMember> */
    private array $memberCache = [];
    
    /** @var array<string, string> Email to member ID mapping */
    private array $emailIndex = [];
    
    private StoreDatabase $database;
    private array $auditLog = [];
    
    public function __construct(private readonly Main $main) {
        $this->database = new StoreDatabase($main);
        $this->loadStoresFromDatabase();
        $this->buildIndexes();
    }

    // Store Management
    public function createStore(
        string $name,
        string $description = '',
        string $address = '',
        string $phone = '',
        string $email = '',
        array $settings = [],
        array $metadata = []
    ): Store {
        $store = new Store($name, $description, $address, $phone, $email, $settings, $metadata);
        
        $this->stores[$store->getId()] = $store;
        $this->saveStoreToDatabase($store);
        $this->logAction('store_created', ['store_id' => $store->getId(), 'name' => $name]);
        
        return $store;
    }

    public function getStore(string $storeId): ?Store {
        return $this->stores[$storeId] ?? null;
    }

    public function getAllStores(): array {
        return $this->stores;
    }

    public function getActiveStores(): array {
        return array_filter($this->stores, fn(Store $store) => $store->isActive());
    }

    public function updateStore(Store $store): bool {
        if (!isset($this->stores[$store->getId()])) {
            return false;
        }
        
        $this->stores[$store->getId()] = $store;
        $this->saveStoreToDatabase($store);
        $this->logAction('store_updated', ['store_id' => $store->getId()]);
        
        return true;
    }

    public function deleteStore(string $storeId): bool {
        if (!isset($this->stores[$storeId])) {
            return false;
        }
        
        $store = $this->stores[$storeId];
        
        // Remove all members from cache
        foreach ($store->getMembers() as $member) {
            unset($this->memberCache[$member->getId()]);
            unset($this->emailIndex[strtolower($member->getEmail())]);
        }
        
        unset($this->stores[$storeId]);
        $this->deleteStoreFromDatabase($storeId);
        $this->logAction('store_deleted', ['store_id' => $storeId]);
        
        return true;
    }

    // Store Member Management
    public function createMember(
        string $storeId,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        StoreMemberRole $role = StoreMemberRole::EMPLOYEE,
        StoreMemberStatus $status = StoreMemberStatus::ACTIVE,
        array $permissions = [],
        float $hourlyWage = 0.0,
        array $workSchedule = [],
        array $metadata = []
    ): ?StoreMember {
        $store = $this->getStore($storeId);
        if (!$store) {
            throw new InvalidArgumentException("Store not found: $storeId");
        }

        // Check if email already exists
        if ($this->getMemberByEmail($email)) {
            throw new InvalidArgumentException("Member with email $email already exists");
        }

        $member = new StoreMember(
            $firstName,
            $lastName,
            $email,
            $phone,
            $role,
            $status,
            $permissions,
            $hourlyWage,
            $workSchedule,
            $metadata
        );

        $store->addMember($member);
        $this->memberCache[$member->getId()] = $member;
        $this->emailIndex[strtolower($email)] = $member->getId();
        
        $this->saveStoreToDatabase($store);
        $this->logAction('member_created', [
            'store_id' => $storeId,
            'member_id' => $member->getId(),
            'email' => $email,
            'role' => $role->value
        ]);

        return $member;
    }

    public function getMember(string $memberId): ?StoreMember {
        return $this->memberCache[$memberId] ?? null;
    }

    public function getMemberByEmail(string $email): ?StoreMember {
        $memberId = $this->emailIndex[strtolower($email)] ?? null;
        return $memberId ? $this->getMember($memberId) : null;
    }

    public function updateMember(string $storeId, StoreMember $member): bool {
        $store = $this->getStore($storeId);
        if (!$store || !$store->hasMember($member->getId())) {
            return false;
        }

        // Update email index if email changed
        $oldMember = $store->getMember($member->getId());
        if ($oldMember && $oldMember->getEmail() !== $member->getEmail()) {
            unset($this->emailIndex[strtolower($oldMember->getEmail())]);
            $this->emailIndex[strtolower($member->getEmail())] = $member->getId();
        }

        $store->updateMember($member);
        $this->memberCache[$member->getId()] = $member;
        
        $this->saveStoreToDatabase($store);
        $this->logAction('member_updated', [
            'store_id' => $storeId,
            'member_id' => $member->getId()
        ]);

        return true;
    }

    public function deleteMember(string $storeId, string $memberId): bool {
        $store = $this->getStore($storeId);
        if (!$store || !$store->hasMember($memberId)) {
            return false;
        }

        $member = $store->getMember($memberId);
        if ($member) {
            unset($this->emailIndex[strtolower($member->getEmail())]);
        }

        $store->removeMember($memberId);
        unset($this->memberCache[$memberId]);
        
        $this->saveStoreToDatabase($store);
        $this->logAction('member_deleted', [
            'store_id' => $storeId,
            'member_id' => $memberId
        ]);

        return true;
    }

    // Member Status Management
    public function activateMember(string $storeId, string $memberId): bool {
        return $this->changeMemberStatus($storeId, $memberId, StoreMemberStatus::ACTIVE);
    }

    public function deactivateMember(string $storeId, string $memberId): bool {
        return $this->changeMemberStatus($storeId, $memberId, StoreMemberStatus::INACTIVE);
    }

    public function suspendMember(string $storeId, string $memberId): bool {
        return $this->changeMemberStatus($storeId, $memberId, StoreMemberStatus::SUSPENDED);
    }

    public function terminateMember(string $storeId, string $memberId): bool {
        return $this->changeMemberStatus($storeId, $memberId, StoreMemberStatus::TERMINATED);
    }

    private function changeMemberStatus(string $storeId, string $memberId, StoreMemberStatus $newStatus): bool {
        $store = $this->getStore($storeId);
        $member = $store?->getMember($memberId);
        
        if (!$store || !$member) {
            return false;
        }

        if (!$member->getStatus()->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from {$member->getStatus()->value} to {$newStatus->value}"
            );
        }

        $member->setStatus($newStatus);
        return $this->updateMember($storeId, $member);
    }

    // Member Role Management
    public function changeMemberRole(string $storeId, string $memberId, StoreMemberRole $newRole): bool {
        $store = $this->getStore($storeId);
        $member = $store?->getMember($memberId);
        
        if (!$store || !$member) {
            return false;
        }

        $member->setRole($newRole);
        return $this->updateMember($storeId, $member);
    }

    // Permission Management
    public function grantPermission(string $storeId, string $memberId, string $permission): bool {
        $store = $this->getStore($storeId);
        $member = $store?->getMember($memberId);
        
        if (!$store || !$member) {
            return false;
        }

        $member->addPermission($permission);
        return $this->updateMember($storeId, $member);
    }

    public function revokePermission(string $storeId, string $memberId, string $permission): bool {
        $store = $this->getStore($storeId);
        $member = $store?->getMember($memberId);
        
        if (!$store || !$member) {
            return false;
        }

        $member->removePermission($permission);
        return $this->updateMember($storeId, $member);
    }

    // Query Methods
    public function getMembersByStore(string $storeId): array {
        $store = $this->getStore($storeId);
        return $store ? $store->getMembers() : [];
    }

    public function getMembersByRole(string $storeId, StoreMemberRole $role): array {
        $store = $this->getStore($storeId);
        return $store ? $store->getMembersByRole($role) : [];
    }

    public function getMembersByStatus(string $storeId, StoreMemberStatus $status): array {
        $store = $this->getStore($storeId);
        return $store ? $store->getMembersByStatus($status) : [];
    }

    public function getActiveMembers(string $storeId): array {
        return $this->getMembersByStatus($storeId, StoreMemberStatus::ACTIVE);
    }

    public function searchMembers(array $criteria): array {
        $results = [];
        
        foreach ($this->memberCache as $member) {
            if ($this->matchesCriteria($member, $criteria)) {
                $results[] = $member;
            }
        }
        
        return $results;
    }

    private function matchesCriteria(StoreMember $member, array $criteria): bool {
        foreach ($criteria as $field => $value) {
            switch ($field) {
                case 'firstName':
                    if (stripos($member->getFirstName(), $value) === false) return false;
                    break;
                case 'lastName':
                    if (stripos($member->getLastName(), $value) === false) return false;
                    break;
                case 'email':
                    if (stripos($member->getEmail(), $value) === false) return false;
                    break;
                case 'role':
                    if ($member->getRole()->value !== $value) return false;
                    break;
                case 'status':
                    if ($member->getStatus()->value !== $value) return false;
                    break;
            }
        }
        
        return true;
    }

    // Statistics and Analytics
    public function getStoreStatistics(string $storeId): array {
        $store = $this->getStore($storeId);
        if (!$store) {
            return [];
        }

        $members = $store->getMembers();
        $statusCounts = [];
        $roleCounts = [];

        foreach ($members as $member) {
            $status = $member->getStatus()->value;
            $role = $member->getRole()->value;
            
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
        }

        return [
            'total_members' => count($members),
            'active_members' => $store->getActiveMemberCount(),
            'status_breakdown' => $statusCounts,
            'role_breakdown' => $roleCounts,
            'avg_hourly_wage' => $this->calculateAverageWage($members),
            'store_info' => [
                'name' => $store->getName(),
                'address' => $store->getAddress(),
                'phone' => $store->getPhone(),
                'email' => $store->getEmail()
            ]
        ];
    }

    private function calculateAverageWage(array $members): float {
        if (empty($members)) {
            return 0.0;
        }

        $totalWage = array_sum(array_map(fn(StoreMember $m) => $m->getHourlyWage(), $members));
        return $totalWage / count($members);
    }

    // Authentication and Login
    public function recordLogin(string $memberId): bool {
        $member = $this->getMember($memberId);
        if (!$member || !$member->isActive()) {
            return false;
        }

        $member->setLastLoginAt();
        
        // Find store containing this member and update
        foreach ($this->stores as $store) {
            if ($store->hasMember($memberId)) {
                $this->saveStoreToDatabase($store);
                break;
            }
        }

        $this->logAction('member_login', ['member_id' => $memberId]);
        return true;
    }

    // Audit and Logging
    public function getAuditLog(int $limit = 100, int $offset = 0): array {
        return array_slice($this->auditLog, $offset, $limit);
    }

    private function logAction(string $action, array $data = []): void {
        $this->auditLog[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        // Keep only last 1000 entries in memory
        if (count($this->auditLog) > 1000) {
            $this->auditLog = array_slice($this->auditLog, -1000);
        }
    }

    // Database Operations
    private function loadStoresFromDatabase(): void {
        // Load stores from database
        // Implementation depends on your database structure
    }

    private function saveStoreToDatabase(Store $store): void {
        // Save store to database
        // Implementation depends on your database structure
    }

    private function deleteStoreFromDatabase(string $storeId): void {
        // Delete store from database
        // Implementation depends on your database structure
    }

    private function buildIndexes(): void {
        foreach ($this->stores as $store) {
            foreach ($store->getMembers() as $member) {
                $this->memberCache[$member->getId()] = $member;
                $this->emailIndex[strtolower($member->getEmail())] = $member->getId();
            }
        }
    }

    // Export and Import
    public function exportStoreData(string $storeId): array {
        $store = $this->getStore($storeId);
        return $store ? $store->toArray() : [];
    }

    public function exportAllStoresData(): array {
        return array_map(fn(Store $store) => $store->toArray(), $this->stores);
    }

    public function importStoreData(array $data): bool {
        try {
            $store = Store::fromArray($data);
            $this->stores[$store->getId()] = $store;
            $this->saveStoreToDatabase($store);
            $this->buildIndexes();
            return true;
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to import store data: " . $e->getMessage());
            return false;
        }
    }
}