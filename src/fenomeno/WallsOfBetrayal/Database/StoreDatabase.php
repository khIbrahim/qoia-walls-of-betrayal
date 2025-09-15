<?php

namespace fenomeno\WallsOfBetrayal\Database;

use fenomeno\WallsOfBetrayal\Class\Store\Store;
use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\DataConnector;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use Generator;
use Throwable;

/**
 * Database layer for store member management
 * Handles persistence of stores and their members
 */
final class StoreDatabase {

    private DataConnector $database;

    public function __construct(private readonly Main $main) {
        $this->database = $main->getDatabase();
        $this->initializeTables();
    }

    /**
     * Initialize database tables for store member system
     */
    private function initializeTables(): void {
        // Create stores table
        $this->database->executeGeneric(
            'store.create.stores_table',
            [],
            null,
            function(?array $result) {
                $this->main->getLogger()->info("Stores table initialized");
            }
        );

        // Create store_members table
        $this->database->executeGeneric(
            'store.create.members_table',
            [],
            null,
            function(?array $result) {
                $this->main->getLogger()->info("Store members table initialized");
            }
        );

        // Create audit log table
        $this->database->executeGeneric(
            'store.create.audit_table',
            [],
            null,
            function(?array $result) {
                $this->main->getLogger()->info("Store audit table initialized");
            }
        );
    }

    /**
     * Save a store to the database
     */
    public function saveStore(Store $store): Generator {
        return Await::promise(function($resolve, $reject) use ($store) {
            try {
                $storeData = [
                    'store_id' => $store->getId(),
                    'name' => $store->getName(),
                    'description' => $store->getDescription(),
                    'address' => $store->getAddress(),
                    'phone' => $store->getPhone(),
                    'email' => $store->getEmail(),
                    'settings' => json_encode($store->getSettings()),
                    'metadata' => json_encode($store->getMetadata()),
                    'is_active' => $store->isActive() ? 1 : 0,
                    'created_at' => $store->getCreatedAt(),
                    'updated_at' => $store->getUpdatedAt()
                ];

                $this->database->executeInsert(
                    'store.save.store',
                    $storeData,
                    function(int $insertId) use ($store, $resolve, $reject) {
                        $this->saveStoreMembers($store, $resolve, $reject);
                    },
                    function(Throwable $error) use ($reject) {
                        $reject($error);
                    }
                );
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    /**
     * Save store members to database
     */
    private function saveStoreMembers(Store $store, callable $resolve, callable $reject): void {
        $members = $store->getMembers();
        
        if (empty($members)) {
            $resolve(true);
            return;
        }

        // First, delete existing members for this store
        $this->database->executeGeneric(
            'store.delete.store_members',
            ['store_id' => $store->getId()],
            null,
            function() use ($store, $members, $resolve, $reject) {
                $this->insertStoreMembers($store->getId(), $members, $resolve, $reject);
            },
            function(Throwable $error) use ($reject) {
                $reject($error);
            }
        );
    }

    /**
     * Insert store members into database
     */
    private function insertStoreMembers(string $storeId, array $members, callable $resolve, callable $reject): void {
        $insertedCount = 0;
        $totalMembers = count($members);

        if ($totalMembers === 0) {
            $resolve(true);
            return;
        }

        foreach ($members as $member) {
            $memberData = [
                'member_id' => $member->getId(),
                'store_id' => $storeId,
                'first_name' => $member->getFirstName(),
                'last_name' => $member->getLastName(),
                'email' => $member->getEmail(),
                'phone' => $member->getPhone(),
                'role' => $member->getRole()->value,
                'status' => $member->getStatus()->value,
                'permissions' => json_encode($member->getPermissions()),
                'hourly_wage' => $member->getHourlyWage(),
                'work_schedule' => json_encode($member->getWorkSchedule()),
                'metadata' => json_encode($member->getMetadata()),
                'created_at' => $member->getCreatedAt(),
                'updated_at' => $member->getUpdatedAt(),
                'last_login_at' => $member->getLastLoginAt()
            ];

            $this->database->executeInsert(
                'store.save.member',
                $memberData,
                function(int $insertId) use (&$insertedCount, $totalMembers, $resolve) {
                    $insertedCount++;
                    if ($insertedCount >= $totalMembers) {
                        $resolve(true);
                    }
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        }
    }

    /**
     * Load a store from database by ID
     */
    public function loadStore(string $storeId): Generator {
        return Await::promise(function($resolve, $reject) use ($storeId) {
            $this->database->executeSelect(
                'store.load.store',
                ['store_id' => $storeId],
                function(array $rows) use ($storeId, $resolve, $reject) {
                    if (empty($rows)) {
                        $resolve(null);
                        return;
                    }

                    $storeData = $rows[0];
                    $this->loadStoreWithMembers($storeData, $resolve, $reject);
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        });
    }

    /**
     * Load store with its members
     */
    private function loadStoreWithMembers(array $storeData, callable $resolve, callable $reject): void {
        $this->database->executeSelect(
            'store.load.store_members',
            ['store_id' => $storeData['store_id']],
            function(array $memberRows) use ($storeData, $resolve) {
                try {
                    // Create store object
                    $store = Store::fromArray([
                        'id' => $storeData['store_id'],
                        'name' => $storeData['name'],
                        'description' => $storeData['description'],
                        'address' => $storeData['address'],
                        'phone' => $storeData['phone'],
                        'email' => $storeData['email'],
                        'settings' => json_decode($storeData['settings'], true) ?: [],
                        'metadata' => json_decode($storeData['metadata'], true) ?: [],
                        'isActive' => (bool)$storeData['is_active'],
                        'createdAt' => $storeData['created_at'],
                        'updatedAt' => $storeData['updated_at']
                    ]);

                    // Add members to store
                    foreach ($memberRows as $memberData) {
                        $member = StoreMember::fromArray([
                            'id' => $memberData['member_id'],
                            'firstName' => $memberData['first_name'],
                            'lastName' => $memberData['last_name'],
                            'email' => $memberData['email'],
                            'phone' => $memberData['phone'],
                            'role' => $memberData['role'],
                            'status' => $memberData['status'],
                            'permissions' => json_decode($memberData['permissions'], true) ?: [],
                            'hourlyWage' => (float)$memberData['hourly_wage'],
                            'workSchedule' => json_decode($memberData['work_schedule'], true) ?: [],
                            'metadata' => json_decode($memberData['metadata'], true) ?: [],
                            'createdAt' => $memberData['created_at'],
                            'updatedAt' => $memberData['updated_at'],
                            'lastLoginAt' => $memberData['last_login_at']
                        ]);

                        $store->addMember($member);
                    }

                    $resolve($store);
                } catch (Throwable $e) {
                    $reject($e);
                }
            },
            function(Throwable $error) use ($reject) {
                $reject($error);
            }
        );
    }

    /**
     * Load all stores from database
     */
    public function loadAllStores(): Generator {
        return Await::promise(function($resolve, $reject) {
            $this->database->executeSelect(
                'store.load.all_stores',
                [],
                function(array $rows) use ($resolve, $reject) {
                    $stores = [];
                    $loadedCount = 0;
                    $totalStores = count($rows);

                    if ($totalStores === 0) {
                        $resolve([]);
                        return;
                    }

                    foreach ($rows as $storeData) {
                        $this->loadStoreWithMembers(
                            $storeData,
                            function(?Store $store) use (&$stores, &$loadedCount, $totalStores, $resolve) {
                                if ($store) {
                                    $stores[$store->getId()] = $store;
                                }
                                $loadedCount++;
                                if ($loadedCount >= $totalStores) {
                                    $resolve($stores);
                                }
                            },
                            function(Throwable $error) use ($reject) {
                                $reject($error);
                            }
                        );
                    }
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        });
    }

    /**
     * Delete a store from database
     */
    public function deleteStore(string $storeId): Generator {
        return Await::promise(function($resolve, $reject) use ($storeId) {
            // First delete all members
            $this->database->executeGeneric(
                'store.delete.store_members',
                ['store_id' => $storeId],
                null,
                function() use ($storeId, $resolve, $reject) {
                    // Then delete the store
                    $this->database->executeGeneric(
                        'store.delete.store',
                        ['store_id' => $storeId],
                        null,
                        function() use ($resolve) {
                            $resolve(true);
                        },
                        function(Throwable $error) use ($reject) {
                            $reject($error);
                        }
                    );
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        });
    }

    /**
     * Log an audit entry
     */
    public function logAudit(string $action, array $data, ?string $userId = null, ?string $ip = null): void {
        $auditData = [
            'action' => $action,
            'data' => json_encode($data),
            'user_id' => $userId,
            'ip_address' => $ip,
            'timestamp' => time()
        ];

        $this->database->executeInsert(
            'store.save.audit',
            $auditData,
            null,
            function(Throwable $error) {
                $this->main->getLogger()->warning("Failed to log audit entry: " . $error->getMessage());
            }
        );
    }

    /**
     * Get audit log entries
     */
    public function getAuditLog(int $limit = 100, int $offset = 0, ?string $action = null): Generator {
        return Await::promise(function($resolve, $reject) use ($limit, $offset, $action) {
            $query = $action ? 'store.load.audit_filtered' : 'store.load.audit';
            $params = [
                'limit' => $limit,
                'offset' => $offset
            ];

            if ($action) {
                $params['action'] = $action;
            }

            $this->database->executeSelect(
                $query,
                $params,
                function(array $rows) use ($resolve) {
                    $auditEntries = array_map(function($row) {
                        return [
                            'id' => $row['id'],
                            'action' => $row['action'],
                            'data' => json_decode($row['data'], true) ?: [],
                            'user_id' => $row['user_id'],
                            'ip_address' => $row['ip_address'],
                            'timestamp' => $row['timestamp']
                        ];
                    }, $rows);
                    
                    $resolve($auditEntries);
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        });
    }

    /**
     * Get store statistics from database
     */
    public function getStoreStatistics(string $storeId): Generator {
        return Await::promise(function($resolve, $reject) use ($storeId) {
            $this->database->executeSelect(
                'store.stats.store',
                ['store_id' => $storeId],
                function(array $rows) use ($resolve) {
                    $stats = [];
                    foreach ($rows as $row) {
                        $stats[] = [
                            'metric' => $row['metric'],
                            'value' => $row['value'],
                            'timestamp' => $row['timestamp']
                        ];
                    }
                    $resolve($stats);
                },
                function(Throwable $error) use ($reject) {
                    $reject($error);
                }
            );
        });
    }
}