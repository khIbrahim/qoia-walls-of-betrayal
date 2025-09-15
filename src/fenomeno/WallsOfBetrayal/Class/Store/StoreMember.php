<?php

namespace fenomeno\WallsOfBetrayal\Class\Store;

use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberStatus;
use pocketmine\utils\TextFormat;

/**
 * Represents a store member in the restaurant POS ecosystem
 * Handles employee data, roles, permissions, and work schedules
 */
final class StoreMember {

    private string $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $phone;
    private StoreMemberRole $role;
    private StoreMemberStatus $status;
    private array $permissions;
    private float $hourlyWage;
    private array $workSchedule;
    private int $createdAt;
    private int $updatedAt;
    private ?int $lastLoginAt;
    private array $metadata;

    public function __construct(
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
    ) {
        $this->id = $this->generateId();
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
        $this->status = $status;
        $this->permissions = $this->mergeWithRolePermissions($permissions, $role);
        $this->hourlyWage = max(0, $hourlyWage);
        $this->workSchedule = $workSchedule;
        $this->metadata = $metadata;
        
        $this->createdAt = time();
        $this->updatedAt = time();
        $this->lastLoginAt = null;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getRole(): StoreMemberRole { return $this->role; }
    public function getStatus(): StoreMemberStatus { return $this->status; }
    public function getPermissions(): array { return $this->permissions; }
    public function getHourlyWage(): float { return $this->hourlyWage; }
    public function getWorkSchedule(): array { return $this->workSchedule; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getUpdatedAt(): int { return $this->updatedAt; }
    public function getLastLoginAt(): ?int { return $this->lastLoginAt; }
    public function getMetadata(): array { return $this->metadata; }

    // Setters with validation
    public function setFirstName(string $firstName): void {
        if (strlen(trim($firstName)) < 1) {
            throw new \InvalidArgumentException("First name cannot be empty");
        }
        $this->firstName = trim($firstName);
        $this->updateTimestamp();
    }

    public function setLastName(string $lastName): void {
        if (strlen(trim($lastName)) < 1) {
            throw new \InvalidArgumentException("Last name cannot be empty");
        }
        $this->lastName = trim($lastName);
        $this->updateTimestamp();
    }

    public function setEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format");
        }
        $this->email = strtolower(trim($email));
        $this->updateTimestamp();
    }

    public function setPhone(string $phone): void {
        $cleanPhone = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
        if (strlen($cleanPhone) < 7) {
            throw new \InvalidArgumentException("Invalid phone number");
        }
        $this->phone = $cleanPhone;
        $this->updateTimestamp();
    }

    public function setRole(StoreMemberRole $role): void {
        $this->role = $role;
        $this->permissions = $this->mergeWithRolePermissions($this->permissions, $role);
        $this->updateTimestamp();
    }

    public function setStatus(StoreMemberStatus $status): void {
        $this->status = $status;
        $this->updateTimestamp();
    }

    public function setHourlyWage(float $hourlyWage): void {
        $this->hourlyWage = max(0, $hourlyWage);
        $this->updateTimestamp();
    }

    public function setWorkSchedule(array $workSchedule): void {
        $this->workSchedule = $workSchedule;
        $this->updateTimestamp();
    }

    public function setLastLoginAt(?int $timestamp = null): void {
        $this->lastLoginAt = $timestamp ?? time();
    }

    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
        $this->updateTimestamp();
    }

    // Permission management
    public function hasPermission(string $permission): bool {
        return in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): void {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
            $this->updateTimestamp();
        }
    }

    public function removePermission(string $permission): void {
        $key = array_search($permission, $this->permissions, true);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
            $this->updateTimestamp();
        }
    }

    public function setPermissions(array $permissions): void {
        $this->permissions = $this->mergeWithRolePermissions($permissions, $this->role);
        $this->updateTimestamp();
    }

    // Status checks
    public function isActive(): bool {
        return $this->status === StoreMemberStatus::ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === StoreMemberStatus::INACTIVE;
    }

    public function isSuspended(): bool {
        return $this->status === StoreMemberStatus::SUSPENDED;
    }

    public function isTerminated(): bool {
        return $this->status === StoreMemberStatus::TERMINATED;
    }

    // Role checks
    public function isManager(): bool {
        return $this->role === StoreMemberRole::MANAGER;
    }

    public function isSupervisor(): bool {
        return $this->role === StoreMemberRole::SUPERVISOR;
    }

    public function isCashier(): bool {
        return $this->role === StoreMemberRole::CASHIER;
    }

    public function isEmployee(): bool {
        return $this->role === StoreMemberRole::EMPLOYEE;
    }

    // Utility methods
    public function activate(): void {
        $this->setStatus(StoreMemberStatus::ACTIVE);
    }

    public function deactivate(): void {
        $this->setStatus(StoreMemberStatus::INACTIVE);
    }

    public function suspend(): void {
        $this->setStatus(StoreMemberStatus::SUSPENDED);
    }

    public function terminate(): void {
        $this->setStatus(StoreMemberStatus::TERMINATED);
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->getFullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'permissions' => $this->permissions,
            'hourlyWage' => $this->hourlyWage,
            'workSchedule' => $this->workSchedule,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'lastLoginAt' => $this->lastLoginAt,
            'metadata' => $this->metadata
        ];
    }

    public function toJson(): string {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public static function fromArray(array $data): self {
        $member = new self(
            firstName: $data['firstName'] ?? '',
            lastName: $data['lastName'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? '',
            role: StoreMemberRole::from($data['role'] ?? 'employee'),
            status: StoreMemberStatus::from($data['status'] ?? 'active'),
            permissions: $data['permissions'] ?? [],
            hourlyWage: (float)($data['hourlyWage'] ?? 0.0),
            workSchedule: $data['workSchedule'] ?? [],
            metadata: $data['metadata'] ?? []
        );

        // Set timestamps if provided
        if (isset($data['createdAt'])) {
            $member->createdAt = $data['createdAt'];
        }
        if (isset($data['updatedAt'])) {
            $member->updatedAt = $data['updatedAt'];
        }
        if (isset($data['lastLoginAt'])) {
            $member->lastLoginAt = $data['lastLoginAt'];
        }
        if (isset($data['id'])) {
            $member->id = $data['id'];
        }

        return $member;
    }

    // Private methods
    private function generateId(): string {
        return 'member_' . uniqid() . '_' . random_int(1000, 9999);
    }

    private function updateTimestamp(): void {
        $this->updatedAt = time();
    }

    private function mergeWithRolePermissions(array $permissions, StoreMemberRole $role): array {
        $rolePermissions = $this->getRolePermissions($role);
        return array_unique(array_merge($rolePermissions, $permissions));
    }

    private function getRolePermissions(StoreMemberRole $role): array {
        return match($role) {
            StoreMemberRole::MANAGER => [
                'view_all_orders',
                'manage_inventory',
                'manage_staff',
                'view_reports',
                'manage_settings',
                'process_refunds',
                'override_prices',
                'access_admin_panel'
            ],
            StoreMemberRole::SUPERVISOR => [
                'view_orders',
                'manage_inventory',
                'view_basic_reports',
                'process_refunds',
                'override_prices'
            ],
            StoreMemberRole::CASHIER => [
                'view_orders',
                'process_payments',
                'basic_inventory_view'
            ],
            StoreMemberRole::EMPLOYEE => [
                'view_orders',
                'basic_operations'
            ]
        };
    }
}