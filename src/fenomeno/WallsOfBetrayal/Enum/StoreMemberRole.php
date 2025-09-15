<?php

namespace fenomeno\WallsOfBetrayal\Enum;

enum StoreMemberRole: string {
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case CASHIER = 'cashier';
    case STAFF = 'staff';
    case INTERN = 'intern';

    public function getDisplayName(): string {
        return match($this) {
            self::OWNER => 'Store Owner',
            self::MANAGER => 'Store Manager',
            self::CASHIER => 'Cashier',
            self::STAFF => 'Staff Member',
            self::INTERN => 'Intern'
        };
    }

    public function getDefaultPermissions(): array {
        return match($this) {
            self::OWNER => [
                'store.manage',
                'store.members.hire',
                'store.members.fire',
                'store.members.manage',
                'store.sales.process',
                'store.sales.refund',
                'store.sales.void',
                'store.inventory.manage',
                'store.reports.view',
                'store.finances.view',
                'store.shifts.manage'
            ],
            self::MANAGER => [
                'store.members.manage',
                'store.sales.process',
                'store.sales.refund',
                'store.sales.void',
                'store.inventory.manage',
                'store.reports.view',
                'store.shifts.manage'
            ],
            self::CASHIER => [
                'store.sales.process',
                'store.sales.refund',
                'store.inventory.view',
                'store.shifts.self'
            ],
            self::STAFF => [
                'store.sales.process',
                'store.inventory.view',
                'store.shifts.self'
            ],
            self::INTERN => [
                'store.inventory.view',
                'store.shifts.self'
            ]
        };
    }

    public function getSalaryRange(): array {
        return match($this) {
            self::OWNER => ['min' => 0, 'max' => 999999],
            self::MANAGER => ['min' => 2000, 'max' => 10000],
            self::CASHIER => ['min' => 1200, 'max' => 3000],
            self::STAFF => ['min' => 800, 'max' => 2500],
            self::INTERN => ['min' => 0, 'max' => 1000]
        };
    }

    public function getCommissionRange(): array {
        return match($this) {
            self::OWNER => ['min' => 0, 'max' => 50],
            self::MANAGER => ['min' => 2, 'max' => 15],
            self::CASHIER => ['min' => 1, 'max' => 8],
            self::STAFF => ['min' => 0.5, 'max' => 5],
            self::INTERN => ['min' => 0, 'max' => 2]
        };
    }

    public function canHireMembers(): bool {
        return match($this) {
            self::OWNER => true,
            self::MANAGER => true,
            default => false
        };
    }

    public function canFireMembers(): bool {
        return match($this) {
            self::OWNER => true,
            default => false
        };
    }

    public function canManageInventory(): bool {
        return match($this) {
            self::OWNER, self::MANAGER => true,
            default => false
        };
    }

    public function canViewReports(): bool {
        return match($this) {
            self::OWNER, self::MANAGER => true,
            default => false
        };
    }

    public function getHierarchyLevel(): int {
        return match($this) {
            self::OWNER => 5,
            self::MANAGER => 4,
            self::CASHIER => 3,
            self::STAFF => 2,
            self::INTERN => 1
        };
    }

    public function canManageRole(StoreMemberRole $targetRole): bool {
        return $this->getHierarchyLevel() > $targetRole->getHierarchyLevel();
    }

    public static function getAllRoles(): array {
        return [
            self::OWNER,
            self::MANAGER,
            self::CASHIER,
            self::STAFF,
            self::INTERN
        ];
    }

    public static function getSelectableRoles(StoreMemberRole $currentUserRole): array {
        return array_filter(
            self::getAllRoles(),
            fn(StoreMemberRole $role) => $currentUserRole->canManageRole($role)
        );
    }
}