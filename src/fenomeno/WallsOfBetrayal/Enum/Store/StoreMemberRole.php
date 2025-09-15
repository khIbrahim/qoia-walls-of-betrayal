<?php

namespace fenomeno\WallsOfBetrayal\Enum\Store;

/**
 * Enumeration for store member roles in the restaurant POS ecosystem
 */
enum StoreMemberRole: string
{
    case MANAGER = 'manager';
    case SUPERVISOR = 'supervisor';
    case CASHIER = 'cashier';
    case EMPLOYEE = 'employee';

    /**
     * Get the display name for the role
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::MANAGER => 'Manager',
            self::SUPERVISOR => 'Supervisor',
            self::CASHIER => 'Cashier',
            self::EMPLOYEE => 'Employee'
        };
    }

    /**
     * Get the description of the role
     */
    public function getDescription(): string
    {
        return match($this) {
            self::MANAGER => 'Full access to all store operations, staff management, and reporting',
            self::SUPERVISOR => 'Supervise staff, manage inventory, process refunds',
            self::CASHIER => 'Process payments, handle orders, basic inventory access',
            self::EMPLOYEE => 'Basic store operations and order handling'
        };
    }

    /**
     * Get role hierarchy level (higher number = higher authority)
     */
    public function getLevel(): int
    {
        return match($this) {
            self::MANAGER => 4,
            self::SUPERVISOR => 3,
            self::CASHIER => 2,
            self::EMPLOYEE => 1
        };
    }

    /**
     * Check if this role has higher authority than another role
     */
    public function hasHigherAuthorityThan(StoreMemberRole $other): bool
    {
        return $this->getLevel() > $other->getLevel();
    }

    /**
     * Check if this role can manage another role
     */
    public function canManage(StoreMemberRole $other): bool
    {
        return $this->getLevel() > $other->getLevel();
    }

    /**
     * Get all available roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
            self::EMPLOYEE
        ];
    }

    /**
     * Get roles that are manageable by this role
     */
    public function getManageableRoles(): array
    {
        $currentLevel = $this->getLevel();
        return array_filter(
            self::getAllRoles(),
            fn(StoreMemberRole $role) => $role->getLevel() < $currentLevel
        );
    }
}