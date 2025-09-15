<?php

namespace fenomeno\WallsOfBetrayal\Class\Store;

use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use JsonSerializable;

final class StoreMember implements JsonSerializable {

    public function __construct(
        private readonly int $id,
        private readonly string $uuid,
        private readonly string $username,
        private readonly string $storeId,
        private StoreMemberRole $role,
        private array $permissions,
        private float $salary,
        private float $commissionRate,
        private float $totalSales,
        private float $totalCommission,
        private int $hireDate,
        private bool $isActive,
        private ?int $lastShiftStart = null,
        private ?int $lastShiftEnd = null,
        private int $createdAt = 0,
        private int $updatedAt = 0
    ) {
        if ($this->createdAt === 0) {
            $this->createdAt = time();
        }
        if ($this->updatedAt === 0) {
            $this->updatedAt = time();
        }
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getUsername(): string { return $this->username; }
    public function getStoreId(): string { return $this->storeId; }
    public function getRole(): StoreMemberRole { return $this->role; }
    public function getPermissions(): array { return $this->permissions; }
    public function getSalary(): float { return $this->salary; }
    public function getCommissionRate(): float { return $this->commissionRate; }
    public function getTotalSales(): float { return $this->totalSales; }
    public function getTotalCommission(): float { return $this->totalCommission; }
    public function getHireDate(): int { return $this->hireDate; }
    public function isActive(): bool { return $this->isActive; }
    public function getLastShiftStart(): ?int { return $this->lastShiftStart; }
    public function getLastShiftEnd(): ?int { return $this->lastShiftEnd; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getUpdatedAt(): int { return $this->updatedAt; }

    // Setters
    public function setRole(StoreMemberRole $role): void {
        $this->role = $role;
        $this->updatedAt = time();
    }

    public function setPermissions(array $permissions): void {
        $this->permissions = $permissions;
        $this->updatedAt = time();
    }

    public function setSalary(float $salary): void {
        $this->salary = $salary;
        $this->updatedAt = time();
    }

    public function setCommissionRate(float $commissionRate): void {
        $this->commissionRate = $commissionRate;
        $this->updatedAt = time();
    }

    public function setActive(bool $isActive): void {
        $this->isActive = $isActive;
        $this->updatedAt = time();
    }

    public function addSale(float $saleAmount): void {
        $this->totalSales += $saleAmount;
        $this->totalCommission += $saleAmount * ($this->commissionRate / 100);
        $this->updatedAt = time();
    }

    public function startShift(): void {
        $this->lastShiftStart = time();
        $this->updatedAt = time();
    }

    public function endShift(): void {
        $this->lastShiftEnd = time();
        $this->updatedAt = time();
    }

    // Utility methods
    public function hasPermission(string $permission): bool {
        return in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): void {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
            $this->updatedAt = time();
        }
    }

    public function removePermission(string $permission): void {
        $this->permissions = array_values(array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        ));
        $this->updatedAt = time();
    }

    public function isOnShift(): bool {
        return $this->lastShiftStart !== null && 
               ($this->lastShiftEnd === null || $this->lastShiftStart > $this->lastShiftEnd);
    }

    public function getCurrentShiftDuration(): int {
        if (!$this->isOnShift()) {
            return 0;
        }
        return time() - $this->lastShiftStart;
    }

    public function canManageStore(): bool {
        return $this->role === StoreMemberRole::OWNER || $this->role === StoreMemberRole::MANAGER;
    }

    public function canProcessSales(): bool {
        return $this->isActive && $this->role !== StoreMemberRole::INTERN;
    }

    public function getEffectivePermissions(): array {
        $rolePermissions = $this->role->getDefaultPermissions();
        return array_unique(array_merge($rolePermissions, $this->permissions));
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->username,
            'store_id' => $this->storeId,
            'role' => $this->role->value,
            'role_display' => $this->role->getDisplayName(),
            'permissions' => $this->permissions,
            'effective_permissions' => $this->getEffectivePermissions(),
            'salary' => $this->salary,
            'commission_rate' => $this->commissionRate,
            'total_sales' => $this->totalSales,
            'total_commission' => $this->totalCommission,
            'hire_date' => $this->hireDate,
            'hire_date_formatted' => date('Y-m-d H:i:s', $this->hireDate),
            'is_active' => $this->isActive,
            'is_on_shift' => $this->isOnShift(),
            'current_shift_duration' => $this->getCurrentShiftDuration(),
            'last_shift_start' => $this->lastShiftStart,
            'last_shift_end' => $this->lastShiftEnd,
            'can_manage_store' => $this->canManageStore(),
            'can_process_sales' => $this->canProcessSales(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function fromDatabaseRow(array $row): self {
        return new self(
            id: (int)$row['id'],
            uuid: $row['uuid'],
            username: $row['username'],
            storeId: $row['store_id'],
            role: StoreMemberRole::from($row['role']),
            permissions: json_decode($row['permissions'] ?? '[]', true),
            salary: (float)$row['salary'],
            commissionRate: (float)$row['commission_rate'],
            totalSales: (float)$row['total_sales'],
            totalCommission: (float)$row['total_commission'],
            hireDate: strtotime($row['hire_date']),
            isActive: (bool)$row['is_active'],
            lastShiftStart: $row['last_shift_start'] ? strtotime($row['last_shift_start']) : null,
            lastShiftEnd: $row['last_shift_end'] ? strtotime($row['last_shift_end']) : null,
            createdAt: strtotime($row['created_at']),
            updatedAt: strtotime($row['updated_at'])
        );
    }
}