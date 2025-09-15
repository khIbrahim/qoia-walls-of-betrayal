<?php

namespace fenomeno\WallsOfBetrayal\Utils\Validation;

use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberStatus;
use InvalidArgumentException;

/**
 * Validation utilities for store member management
 * Provides comprehensive validation for all store member data
 */
final class StoreMemberValidator {

    private const MIN_NAME_LENGTH = 1;
    private const MAX_NAME_LENGTH = 255;
    private const MIN_PHONE_LENGTH = 7;
    private const MAX_PHONE_LENGTH = 32;
    private const MAX_EMAIL_LENGTH = 255;
    private const MIN_WAGE = 0.0;
    private const MAX_WAGE = 999.99;

    /**
     * Validate store member data for creation
     */
    public static function validateCreateData(array $data): array {
        $errors = [];

        // Validate required fields
        $required = ['firstName', 'lastName', 'email', 'phone'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        // Validate individual fields
        self::validateName($data['firstName'], 'First name');
        self::validateName($data['lastName'], 'Last name');
        self::validateEmail($data['email']);
        self::validatePhone($data['phone']);

        if (isset($data['role'])) {
            self::validateRole($data['role']);
        }

        if (isset($data['status'])) {
            self::validateStatus($data['status']);
        }

        if (isset($data['hourlyWage'])) {
            self::validateHourlyWage($data['hourlyWage']);
        }

        if (isset($data['permissions']) && !is_array($data['permissions'])) {
            throw new InvalidArgumentException('Permissions must be an array');
        }

        if (isset($data['workSchedule']) && !is_array($data['workSchedule'])) {
            throw new InvalidArgumentException('Work schedule must be an array');
        }

        if (isset($data['metadata']) && !is_array($data['metadata'])) {
            throw new InvalidArgumentException('Metadata must be an array');
        }

        return $data;
    }

    /**
     * Validate store member data for update
     */
    public static function validateUpdateData(array $data): array {
        if (isset($data['firstName'])) {
            self::validateName($data['firstName'], 'First name');
        }

        if (isset($data['lastName'])) {
            self::validateName($data['lastName'], 'Last name');
        }

        if (isset($data['email'])) {
            self::validateEmail($data['email']);
        }

        if (isset($data['phone'])) {
            self::validatePhone($data['phone']);
        }

        if (isset($data['role'])) {
            self::validateRole($data['role']);
        }

        if (isset($data['status'])) {
            self::validateStatus($data['status']);
        }

        if (isset($data['hourlyWage'])) {
            self::validateHourlyWage($data['hourlyWage']);
        }

        if (isset($data['permissions']) && !is_array($data['permissions'])) {
            throw new InvalidArgumentException('Permissions must be an array');
        }

        if (isset($data['workSchedule']) && !is_array($data['workSchedule'])) {
            throw new InvalidArgumentException('Work schedule must be an array');
        }

        if (isset($data['metadata']) && !is_array($data['metadata'])) {
            throw new InvalidArgumentException('Metadata must be an array');
        }

        return $data;
    }

    /**
     * Validate name fields (firstName, lastName)
     */
    public static function validateName(string $name, string $fieldName = 'Name'): void {
        $trimmed = trim($name);
        
        if (strlen($trimmed) < self::MIN_NAME_LENGTH) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }

        if (strlen($trimmed) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException("{$fieldName} cannot exceed " . self::MAX_NAME_LENGTH . " characters");
        }

        // Check for potentially harmful characters
        if (preg_match('/[<>"\']/', $trimmed)) {
            throw new InvalidArgumentException("{$fieldName} contains invalid characters");
        }
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): void {
        $trimmed = trim($email);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        if (strlen($trimmed) > self::MAX_EMAIL_LENGTH) {
            throw new InvalidArgumentException('Email cannot exceed ' . self::MAX_EMAIL_LENGTH . ' characters');
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Additional email security checks
        if (preg_match('/[<>"\']/', $trimmed)) {
            throw new InvalidArgumentException('Email contains invalid characters');
        }
    }

    /**
     * Validate phone number
     */
    public static function validatePhone(string $phone): void {
        $cleaned = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
        
        if (strlen($cleaned) < self::MIN_PHONE_LENGTH) {
            throw new InvalidArgumentException('Phone number is too short');
        }

        if (strlen($cleaned) > self::MAX_PHONE_LENGTH) {
            throw new InvalidArgumentException('Phone number is too long');
        }

        // Basic format validation (at least some digits)
        if (!preg_match('/\d/', $cleaned)) {
            throw new InvalidArgumentException('Phone number must contain digits');
        }
    }

    /**
     * Validate member role
     */
    public static function validateRole(string $role): void {
        try {
            StoreMemberRole::from($role);
        } catch (\ValueError $e) {
            $validRoles = array_map(fn($r) => $r->value, StoreMemberRole::cases());
            throw new InvalidArgumentException(
                "Invalid role '{$role}'. Valid roles are: " . implode(', ', $validRoles)
            );
        }
    }

    /**
     * Validate member status
     */
    public static function validateStatus(string $status): void {
        try {
            StoreMemberStatus::from($status);
        } catch (\ValueError $e) {
            $validStatuses = array_map(fn($s) => $s->value, StoreMemberStatus::cases());
            throw new InvalidArgumentException(
                "Invalid status '{$status}'. Valid statuses are: " . implode(', ', $validStatuses)
            );
        }
    }

    /**
     * Validate hourly wage
     */
    public static function validateHourlyWage(float $wage): void {
        if ($wage < self::MIN_WAGE) {
            throw new InvalidArgumentException('Hourly wage cannot be negative');
        }

        if ($wage > self::MAX_WAGE) {
            throw new InvalidArgumentException('Hourly wage cannot exceed ' . self::MAX_WAGE);
        }
    }

    /**
     * Validate permission string
     */
    public static function validatePermission(string $permission): void {
        $trimmed = trim($permission);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Permission cannot be empty');
        }

        // Basic permission format validation
        if (!preg_match('/^[a-z_]+$/', $trimmed)) {
            throw new InvalidArgumentException('Permission must contain only lowercase letters and underscores');
        }

        if (strlen($trimmed) > 100) {
            throw new InvalidArgumentException('Permission name is too long');
        }
    }

    /**
     * Validate work schedule data
     */
    public static function validateWorkSchedule(array $schedule): void {
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($schedule as $day => $hours) {
            if (!in_array(strtolower($day), $validDays)) {
                throw new InvalidArgumentException("Invalid day: {$day}");
            }

            if (!is_array($hours)) {
                throw new InvalidArgumentException("Hours for {$day} must be an array");
            }

            foreach ($hours as $shift) {
                if (!isset($shift['start']) || !isset($shift['end'])) {
                    throw new InvalidArgumentException("Each shift must have 'start' and 'end' times");
                }

                if (!self::isValidTime($shift['start']) || !self::isValidTime($shift['end'])) {
                    throw new InvalidArgumentException("Invalid time format in {$day} shift");
                }
            }
        }
    }

    /**
     * Validate time format (HH:MM)
     */
    private static function isValidTime(string $time): bool {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * Validate status transition
     */
    public static function validateStatusTransition(StoreMemberStatus $currentStatus, StoreMemberStatus $newStatus): void {
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from {$currentStatus->value} to {$newStatus->value}"
            );
        }
    }

    /**
     * Validate role change permission
     */
    public static function validateRoleChangePermission(
        StoreMemberRole $currentUserRole, 
        StoreMemberRole $targetMemberRole, 
        StoreMemberRole $newRole
    ): void {
        // Only managers can change roles of other managers
        if ($targetMemberRole === StoreMemberRole::MANAGER && $currentUserRole !== StoreMemberRole::MANAGER) {
            throw new InvalidArgumentException('Only managers can modify other managers');
        }

        // Users can only assign roles lower than their own
        if (!$currentUserRole->canManage($newRole)) {
            throw new InvalidArgumentException(
                "Role {$currentUserRole->value} cannot assign role {$newRole->value}"
            );
        }
    }

    /**
     * Sanitize and validate search criteria
     */
    public static function validateSearchCriteria(array $criteria): array {
        $validFields = ['firstName', 'lastName', 'email', 'role', 'status'];
        $sanitized = [];

        foreach ($criteria as $field => $value) {
            if (!in_array($field, $validFields)) {
                continue; // Skip invalid fields
            }

            if ($field === 'role') {
                self::validateRole($value);
            } elseif ($field === 'status') {
                self::validateStatus($value);
            } else {
                // Sanitize string values
                $value = trim($value);
                if (strlen($value) > 255) {
                    throw new InvalidArgumentException("Search term for {$field} is too long");
                }
            }

            $sanitized[$field] = $value;
        }

        return $sanitized;
    }

    /**
     * Validate pagination parameters
     */
    public static function validatePagination(array $params): array {
        $page = (int)($params['page'] ?? 1);
        $perPage = (int)($params['per_page'] ?? 25);

        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than 0');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100');
        }

        return ['page' => $page, 'per_page' => $perPage];
    }
}