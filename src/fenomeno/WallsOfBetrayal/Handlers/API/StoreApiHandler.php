<?php

namespace fenomeno\WallsOfBetrayal\Handlers\API;

use fenomeno\WallsOfBetrayal\Class\Store\Store;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\StoreMemberManager;
use InvalidArgumentException;
use Throwable;

/**
 * API Handler for Store operations
 * Provides REST-like interface for store management
 */
final class StoreApiHandler {

    private StoreMemberManager $memberManager;

    public function __construct(private readonly Main $main) {
        $this->memberManager = $main->getStoreMemberManager();
    }

    /**
     * Create a new store
     * POST /api/stores
     */
    public function createStore(array $data): array {
        try {
            $this->validateCreateStoreData($data);

            $store = $this->memberManager->createStore(
                name: $data['name'],
                description: $data['description'] ?? '',
                address: $data['address'] ?? '',
                phone: $data['phone'] ?? '',
                email: $data['email'] ?? '',
                settings: $data['settings'] ?? [],
                metadata: $data['metadata'] ?? []
            );

            return $this->successResponse([
                'store' => $store->toArray(),
                'message' => 'Store created successfully'
            ], 201);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to create store: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get a store by ID
     * GET /api/stores/{storeId}
     */
    public function getStore(string $storeId, bool $includeMembers = true): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            $storeData = $store->toArray();
            
            if (!$includeMembers) {
                unset($storeData['members']);
            }

            return $this->successResponse([
                'store' => $storeData
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get store: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Update a store
     * PUT /api/stores/{storeId}
     */
    public function updateStore(string $storeId, array $data): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            $this->updateStoreFromData($store, $data);
            
            $success = $this->memberManager->updateStore($store);
            
            if (!$success) {
                return $this->errorResponse('Failed to update store', 500);
            }

            return $this->successResponse([
                'store' => $store->toArray(),
                'message' => 'Store updated successfully'
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to update store: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Delete a store
     * DELETE /api/stores/{storeId}
     */
    public function deleteStore(string $storeId): array {
        try {
            $success = $this->memberManager->deleteStore($storeId);
            
            if (!$success) {
                return $this->errorResponse('Store not found', 404);
            }

            return $this->successResponse([
                'message' => 'Store deleted successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to delete store: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get all stores
     * GET /api/stores
     */
    public function getAllStores(array $filters = []): array {
        try {
            $stores = $this->memberManager->getAllStores();

            // Apply filters
            if (isset($filters['active_only']) && $filters['active_only']) {
                $stores = array_filter($stores, fn(Store $store) => $store->isActive());
            }

            if (isset($filters['search'])) {
                $search = strtolower($filters['search']);
                $stores = array_filter($stores, function(Store $store) use ($search) {
                    return (
                        str_contains(strtolower($store->getName()), $search) ||
                        str_contains(strtolower($store->getDescription()), $search) ||
                        str_contains(strtolower($store->getAddress()), $search)
                    );
                });
            }

            // Apply pagination
            $page = (int)($filters['page'] ?? 1);
            $perPage = min(100, (int)($filters['per_page'] ?? 25));
            $offset = ($page - 1) * $perPage;
            
            $total = count($stores);
            $stores = array_slice($stores, $offset, $perPage, true);

            $includeMembers = !isset($filters['exclude_members']) || !$filters['exclude_members'];

            $storeData = array_map(function(Store $store) use ($includeMembers) {
                $data = $store->toArray();
                if (!$includeMembers) {
                    unset($data['members']);
                }
                return $data;
            }, array_values($stores));

            return $this->successResponse([
                'stores' => $storeData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get stores: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get active stores only
     * GET /api/stores/active
     */
    public function getActiveStores(): array {
        try {
            $stores = $this->memberManager->getActiveStores();

            $storeData = array_map(fn(Store $store) => $store->toArray(), array_values($stores));

            return $this->successResponse([
                'stores' => $storeData,
                'total' => count($storeData)
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get active stores: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Update store settings
     * PATCH /api/stores/{storeId}/settings
     */
    public function updateStoreSettings(string $storeId, array $settings): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            // Merge with existing settings
            $currentSettings = $store->getSettings();
            $newSettings = array_merge($currentSettings, $settings);
            
            $store->setSettings($newSettings);
            
            $success = $this->memberManager->updateStore($store);
            
            if (!$success) {
                return $this->errorResponse('Failed to update store settings', 500);
            }

            return $this->successResponse([
                'settings' => $store->getSettings(),
                'message' => 'Store settings updated successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to update store settings: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get store settings
     * GET /api/stores/{storeId}/settings
     */
    public function getStoreSettings(string $storeId): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            return $this->successResponse([
                'settings' => $store->getSettings()
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to get store settings: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Update store metadata
     * PATCH /api/stores/{storeId}/metadata
     */
    public function updateStoreMetadata(string $storeId, array $metadata): array {
        try {
            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            // Merge with existing metadata
            $currentMetadata = $store->getMetadata();
            $newMetadata = array_merge($currentMetadata, $metadata);
            
            $store->setMetadata($newMetadata);
            
            $success = $this->memberManager->updateStore($store);
            
            if (!$success) {
                return $this->errorResponse('Failed to update store metadata', 500);
            }

            return $this->successResponse([
                'metadata' => $store->getMetadata(),
                'message' => 'Store metadata updated successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to update store metadata: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Export store data
     * GET /api/stores/{storeId}/export
     */
    public function exportStoreData(string $storeId): array {
        try {
            $data = $this->memberManager->exportStoreData($storeId);

            if (empty($data)) {
                return $this->errorResponse('Store not found', 404);
            }

            return $this->successResponse([
                'export_data' => $data,
                'exported_at' => time()
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to export store data: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Import store data
     * POST /api/stores/import
     */
    public function importStoreData(array $data): array {
        try {
            $success = $this->memberManager->importStoreData($data);

            if (!$success) {
                return $this->errorResponse('Failed to import store data', 400);
            }

            return $this->successResponse([
                'message' => 'Store data imported successfully'
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to import store data: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Export all stores data
     * GET /api/stores/export
     */
    public function exportAllStoresData(): array {
        try {
            $data = $this->memberManager->exportAllStoresData();

            return $this->successResponse([
                'export_data' => $data,
                'total_stores' => count($data),
                'exported_at' => time()
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to export all stores data: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Activate/Deactivate store
     * PATCH /api/stores/{storeId}/status
     */
    public function updateStoreStatus(string $storeId, array $data): array {
        try {
            if (!isset($data['active'])) {
                return $this->errorResponse('Active status is required', 400);
            }

            $store = $this->memberManager->getStore($storeId);
            
            if (!$store) {
                return $this->errorResponse('Store not found', 404);
            }

            $store->setActive((bool)$data['active']);
            
            $success = $this->memberManager->updateStore($store);
            
            if (!$success) {
                return $this->errorResponse('Failed to update store status', 500);
            }

            $statusText = $store->isActive() ? 'activated' : 'deactivated';

            return $this->successResponse([
                'store' => $store->toArray(),
                'message' => "Store {$statusText} successfully"
            ]);

        } catch (Throwable $e) {
            $this->main->getLogger()->error("Failed to update store status: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    // Private helper methods
    private function validateCreateStoreData(array $data): void {
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new InvalidArgumentException("Store name is required");
        }

        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }
    }

    private function updateStoreFromData(Store $store, array $data): void {
        if (isset($data['name'])) {
            $store->setName($data['name']);
        }

        if (isset($data['description'])) {
            $store->setDescription($data['description']);
        }

        if (isset($data['address'])) {
            $store->setAddress($data['address']);
        }

        if (isset($data['phone'])) {
            $store->setPhone($data['phone']);
        }

        if (isset($data['email'])) {
            $store->setEmail($data['email']);
        }

        if (isset($data['settings'])) {
            $store->setSettings($data['settings']);
        }

        if (isset($data['metadata'])) {
            $store->setMetadata($data['metadata']);
        }

        if (isset($data['isActive'])) {
            $store->setActive((bool)$data['isActive']);
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