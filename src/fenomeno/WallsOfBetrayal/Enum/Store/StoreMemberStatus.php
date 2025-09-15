<?php

namespace fenomeno\WallsOfBetrayal\Enum\Store;

/**
 * Enumeration for store member status in the restaurant POS ecosystem
 */
enum StoreMemberStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    /**
     * Get the display name for the status
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated'
        };
    }

    /**
     * Get the description of the status
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ACTIVE => 'Member is actively working and has full access',
            self::INACTIVE => 'Member is temporarily not working but can be reactivated',
            self::SUSPENDED => 'Member is suspended and has limited or no access',
            self::TERMINATED => 'Member has been terminated and cannot access the system'
        };
    }

    /**
     * Get the color code for UI display
     */
    public function getColorCode(): string
    {
        return match($this) {
            self::ACTIVE => '#28a745',     // Green
            self::INACTIVE => '#ffc107',   // Yellow
            self::SUSPENDED => '#fd7e14',  // Orange
            self::TERMINATED => '#dc3545'  // Red
        };
    }

    /**
     * Check if the status allows system access
     */
    public function allowsAccess(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            self::INACTIVE => false,
            self::SUSPENDED => false,
            self::TERMINATED => false
        };
    }

    /**
     * Check if the status can be changed to active
     */
    public function canBeActivated(): bool
    {
        return match($this) {
            self::ACTIVE => false,
            self::INACTIVE => true,
            self::SUSPENDED => true,
            self::TERMINATED => false
        };
    }

    /**
     * Check if this is a temporary status
     */
    public function isTemporary(): bool
    {
        return match($this) {
            self::ACTIVE => false,
            self::INACTIVE => true,
            self::SUSPENDED => true,
            self::TERMINATED => false
        };
    }

    /**
     * Get valid transition statuses from current status
     */
    public function getValidTransitions(): array
    {
        return match($this) {
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED, self::TERMINATED],
            self::INACTIVE => [self::ACTIVE, self::SUSPENDED, self::TERMINATED],
            self::SUSPENDED => [self::ACTIVE, self::INACTIVE, self::TERMINATED],
            self::TERMINATED => [] // Terminated is final
        };
    }

    /**
     * Check if transition to another status is valid
     */
    public function canTransitionTo(StoreMemberStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getValidTransitions(), true);
    }

    /**
     * Get all available statuses
     */
    public static function getAllStatuses(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::SUSPENDED,
            self::TERMINATED
        ];
    }

    /**
     * Get active statuses (statuses that allow some level of access)
     */
    public static function getActiveStatuses(): array
    {
        return array_filter(
            self::getAllStatuses(),
            fn(StoreMemberStatus $status) => $status->allowsAccess()
        );
    }
}