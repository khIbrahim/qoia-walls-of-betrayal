<?php

namespace fenomeno\WallsOfBetrayal\Handlers\API;

use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberStatus;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\StoreMemberManager;
use Generator;
use InvalidArgumentException;
use Throwable;

/**
 * API Handler for Store Member operations
 * Provides REST-like interface for external systems (Laravel-friendly)
 */
final class StoreMemberApiHandler {

    private StoreMemberManager $memberManager;

    public function __construct(private readonly Main $main) {
        $this->memberManager = $main->getStoreMemberManager();
    }

    /**
     * Create a new store member
     * POST /api/store/{storeId}/members
     */
    public function createMember(string $storeId, array $data): array {
        try {
            $this->validateCreateMemberData($data);

            $member = $this->memberManager->createMember(
                storeId: $storeId,
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                email: $data['email'],
                phone: $data['phone'],
                role: StoreMemberRole::from($data['role'] ?? 'employee'),
                status: StoreMemberStatus::from($data['status'] ?? 'active'),
                permissions: $data['permissions'] ?? [],
                hourlyWage: (float)($data['hourlyWage'] ?? 0.0),
                workSchedule: $data['workSchedule'] ?? [],
                metadata: $data['metadata'] ?? []
            );

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Store member created successfully'
            ], 201);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to create member: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get a store member by ID
     * GET /api/store/{storeId}/members/{memberId}
     */
    public function getMember(string $storeId, string $memberId): array {
        try {
            $member = $this->memberManager->getMember($memberId);
            
            if (!$member) {
                return $this->errorResponse('Member not found', 404);
            }

            // Verify member belongs to the store
            $store = $this->memberManager->getStore($storeId);
            if (!$store || !$store->hasMember($memberId)) {
                return $this->errorResponse('Member not found in this store', 404);
            }

            return $this->successResponse([
                'member' => $member->toArray()
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get member: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Update a store member
     * PUT /api/store/{storeId}/members/{memberId}
     */
    public function updateMember(string $storeId, string $memberId, array $data): array {
        try {
            $member = $this->memberManager->getMember($memberId);
            
            if (!$member) {
                return $this->errorResponse('Member not found', 404);
            }

            $this->updateMemberFromData($member, $data);
            
            $success = $this->memberManager->updateMember($storeId, $member);
            
            if (!$success) {
                return $this->errorResponse('Failed to update member', 500);
            }

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Store member updated successfully'
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to update member: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Delete a store member
     * DELETE /api/store/{storeId}/members/{memberId}
     */
    public function deleteMember(string $storeId, string $memberId): array {
        try {
            $success = $this->memberManager->deleteMember($storeId, $memberId);
            
            if (!$success) {
                return $this->errorResponse('Member not found', 404);
            }

            return $this->successResponse([
                'message' => 'Store member deleted successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to delete member: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get all members for a store
     * GET /api/store/{storeId}/members
     */
    public function getStoreMembers(string $storeId, array $filters = []): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            $members = $store->getMembers();

            // Apply filters
            if (isset($filters['role'])) {
                $role = StoreMemberRole::from($filters['role']);
                $members = array_filter($members, fn($m) => $m->getRole() === $role);
            }

            if (isset($filters['status'])) {
                $status = StoreMemberStatus::from($filters['status']);
                $members = array_filter($members, fn($m) => $m->getStatus() === $status);
            }

            if (isset($filters['search'])) {
                $search = strtolower($filters['search']);
                $members = array_filter($members, function($member) use ($search) {
                    return (
                        str_contains(strtolower($member->getFirstName()), $search) ||
                        str_contains(strtolower($member->getLastName()), $search) ||
                        str_contains(strtolower($member->getEmail()), $search)
                    );
                });
            }

            // Apply pagination
            $page = (int)($filters['page'] ?? 1);
            $perPage = min(100, (int)($filters['per_page'] ?? 25));
            $offset = ($page - 1) * $perPage;
            
            $total = count($members);
            $members = array_slice($members, $offset, $perPage);

            return $this->successResponse([
                'members' => array_map(fn($m) => $m->toArray(), array_values($members)),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get store members: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Change member status
     * PATCH /api/store/{storeId}/members/{memberId}/status
     */
    public function changeMemberStatus(string $storeId, string $memberId, array $data): array {
        try {
            if (!isset($data['status'])) {
                return $this->errorResponse('Status is required', 400);
            }

            $newStatus = StoreMemberStatus::from($data['status']);
            $success = false;

            switch ($newStatus) {
                case StoreMemberStatus::ACTIVE:
                    $success = $this->memberManager->activateMember($storeId, $memberId);
                    break;
                case StoreMemberStatus::INACTIVE:
                    $success = $this->memberManager->deactivateMember($storeId, $memberId);
                    break;
                case StoreMemberStatus::SUSPENDED:
                    $success = $this->memberManager->suspendMember($storeId, $memberId);
                    break;
                case StoreMemberStatus::TERMINATED:
                    $success = $this->memberManager->terminateMember($storeId, $memberId);
                    break;
            }

            if (!$success) {
                return $this->errorResponse('Failed to change member status', 400);
            }

            $member = $this->memberManager->getMember($memberId);

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Member status updated successfully'
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to change member status: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Change member role
     * PATCH /api/store/{storeId}/members/{memberId}/role
     */
    public function changeMemberRole(string $storeId, string $memberId, array $data): array {
        try {
            if (!isset($data['role'])) {
                return $this->errorResponse('Role is required', 400);
            }

            $newRole = StoreMemberRole::from($data['role']);
            $success = $this->memberManager->changeMemberRole($storeId, $memberId, $newRole);

            if (!$success) {
                return $this->errorResponse('Failed to change member role', 400);
            }

            $member = $this->memberManager->getMember($memberId);

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Member role updated successfully'
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to change member role: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Grant permission to member
     * POST /api/store/{storeId}/members/{memberId}/permissions
     */
    public function grantPermission(string $storeId, string $memberId, array $data): array {
        try {
            if (!isset($data['permission'])) {
                return $this->errorResponse('Permission is required', 400);
            }

            $success = $this->memberManager->grantPermission($storeId, $memberId, $data['permission']);

            if (!$success) {
                return $this->errorResponse('Failed to grant permission', 400);
            }

            $member = $this->memberManager->getMember($memberId);

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Permission granted successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to grant permission: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Revoke permission from member
     * DELETE /api/store/{storeId}/members/{memberId}/permissions/{permission}
     */
    public function revokePermission(string $storeId, string $memberId, string $permission): array {
        try {
            $success = $this->memberManager->revokePermission($storeId, $memberId, $permission);

            if (!$success) {
                return $this->errorResponse('Failed to revoke permission', 400);
            }

            $member = $this->memberManager->getMember($memberId);

            return $this->successResponse([
                'member' => $member->toArray(),
                'message' => 'Permission revoked successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to revoke permission: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get store statistics
     * GET /api/store/{storeId}/statistics
     */
    public function getStoreStatistics(string $storeId): array {
        try {
            $statistics = $this->memberManager->getStoreStatistics($storeId);

            if (empty($statistics)) {
                return $this->errorResponse('Store not found', 404);
            }

            return $this->successResponse([
                'statistics' => $statistics
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get store statistics: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Record member login
     * POST /api/store/{storeId}/members/{memberId}/login
     */
    public function recordLogin(string $storeId, string $memberId): array {
        try {
            $success = $this->memberManager->recordLogin($memberId);

            if (!$success) {
                return $this->errorResponse('Member not found or inactive', 404);
            }

            return $this->successResponse([
                'message' => 'Login recorded successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to record login: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Search members across stores
     * GET /api/members/search
     */
    public function searchMembers(array $criteria): array {
        try {
            $members = $this->memberManager->searchMembers($criteria);

            return $this->successResponse([
                'members' => array_map(fn($m) => $m->toArray(), $members),
                'total' => count($members)
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to search members: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    // Private helper methods
    private function validateCreateMemberData(array $data): void {
        $required = ['firstName', 'lastName', 'email', 'phone'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new InvalidArgumentException("Field '$field' is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }

        if (isset($data['role'])) {
            try {
                StoreMemberRole::from($data['role']);
            } catch (Throwable) {
                throw new InvalidArgumentException("Invalid role: {$data['role']}");
            }
        }

        if (isset($data['status'])) {
            try {
                StoreMemberStatus::from($data['status']);
            } catch (Throwable) {
                throw new InvalidArgumentException("Invalid status: {$data['status']}");
            }
        }
    }

    private function updateMemberFromData(StoreMember $member, array $data): void {
        if (isset($data['firstName'])) {
            $member->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $member->setLastName($data['lastName']);
        }

        if (isset($data['email'])) {
            $member->setEmail($data['email']);
        }

        if (isset($data['phone'])) {
            $member->setPhone($data['phone']);
        }

        if (isset($data['role'])) {
            $member->setRole(StoreMemberRole::from($data['role']));
        }

        if (isset($data['status'])) {
            $member->setStatus(StoreMemberStatus::from($data['status']));
        }

        if (isset($data['hourlyWage'])) {
            $member->setHourlyWage((float)$data['hourlyWage']);
        }

        if (isset($data['workSchedule'])) {
            $member->setWorkSchedule($data['workSchedule']);
        }

        if (isset($data['permissions'])) {
            $member->setPermissions($data['permissions']);
        }

        if (isset($data['metadata'])) {
            $member->setMetadata($data['metadata']);
        }
    }

    private function successResponse(array $data, int $statusCode = 200): array {
        return [
            'success' => true,
            'status_code' => $statusCode,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    private function errorResponse(string $message, int $statusCode = 400): array {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ],
            'timestamp' => time()
        ];
    }
}