<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use DateTimeImmutable;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMember;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMemberSession;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\StoreMemberRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;

class StoreMemberRepository implements StoreMemberRepositoryInterface
{
    public function __construct(private readonly Main $main) {}

    public function init(DatabaseManager $database): void
    {
        // Initialize store_members table
        $database->executeGeneric('store_members.init', [], function () {
            $this->main->getLogger()->info("§aTable `store_members` has been successfully initialized");
        });

        // Initialize store_member_sessions table
        $database->executeGeneric('store_member_sessions.init', [], function () {
            $this->main->getLogger()->info("§aTable `store_member_sessions` has been successfully initialized");
        });
    }

    public function loadByUuid(string $uuid): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function () use ($resolver, $uuid) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect(
                    'store_members.load_by_uuid',
                    ['uuid' => $uuid]
                );

                if (empty($data)) {
                    $resolver->resolve(null);
                    return;
                }

                $memberData = $data[0];
                $member = $this->mapRowToStoreMember($memberData);
                $resolver->resolve($member);
            } catch (\Throwable $e) {
                $resolver->reject($e);
            }
        });

        return $resolver->getPromise();
    }

    public function loadByUsername(string $username): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function () use ($resolver, $username) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect(
                    'store_members.load_by_username',
                    ['username' => strtolower($username)]
                );

                if (empty($data)) {
                    $resolver->resolve(null);
                    return;
                }

                $memberData = $data[0];
                $member = $this->mapRowToStoreMember($memberData);
                $resolver->resolve($member);
            } catch (\Throwable $e) {
                $resolver->reject($e);
            }
        });

        return $resolver->getPromise();
    }

    public function insert(StoreMember $member): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(
            'store_members.insert',
            [
                'uuid' => $member->getUuid(),
                'username' => strtolower($member->getUsername()),
                'role' => $member->getRole()->value,
                'status' => $member->getStatus()->value,
                'hired_at' => $member->getHiredAt()->format('Y-m-d H:i:s'),
                'last_login' => $member->getLastLogin()?->format('Y-m-d H:i:s'),
                'total_working_hours' => $member->getTotalWorkingHours(),
                'total_transactions' => $member->getTotalTransactions(),
                'total_sales' => $member->getTotalSales(),
                'permissions' => json_encode($member->getPermissions()),
                'store_id' => $member->getStoreId(),
                'last_promotion_at' => $member->getLastPromotionAt()?->format('Y-m-d H:i:s'),
                'notes' => $member->getNotes()
            ]
        );
    }

    public function update(StoreMember $member): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(
            'store_members.update',
            [
                'uuid' => $member->getUuid(),
                'username' => strtolower($member->getUsername()),
                'role' => $member->getRole()->value,
                'status' => $member->getStatus()->value,
                'last_login' => $member->getLastLogin()?->format('Y-m-d H:i:s'),
                'total_working_hours' => $member->getTotalWorkingHours(),
                'total_transactions' => $member->getTotalTransactions(),
                'total_sales' => $member->getTotalSales(),
                'permissions' => json_encode($member->getPermissions()),
                'store_id' => $member->getStoreId(),
                'last_promotion_at' => $member->getLastPromotionAt()?->format('Y-m-d H:i:s'),
                'notes' => $member->getNotes()
            ]
        );
    }

    public function delete(string $uuid): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(
            'store_members.delete',
            ['uuid' => $uuid]
        );
    }

    public function getAll(): Promise
    {
        return $this->executeSelectQuery('store_members.get_all', []);
    }

    public function getByRole(StoreMemberRole $role): Promise
    {
        return $this->executeSelectQuery('store_members.get_by_role', ['role' => $role->value]);
    }

    public function getByStatus(StoreMemberStatus $status): Promise
    {
        return $this->executeSelectQuery('store_members.get_by_status', ['status' => $status->value]);
    }

    public function getByStoreId(?string $storeId): Promise
    {
        return $this->executeSelectQuery('store_members.get_by_store_id', ['store_id' => $storeId]);
    }

    public function updateLastLogin(string $uuid): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(
            'store_members.update_last_login',
            [
                'uuid' => $uuid,
                'last_login' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ]
        );
    }

    public function updateWorkStats(string $uuid, int $additionalHours, float $additionalSales, int $additionalTransactions): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(
            'store_members.update_work_stats',
            [
                'uuid' => $uuid,
                'additional_hours' => $additionalHours,
                'additional_sales' => $additionalSales,
                'additional_transactions' => $additionalTransactions
            ]
        );
    }

    public function getTopPerformersBySales(int $limit = 10): Promise
    {
        return $this->executeSelectQuery('store_members.get_top_by_sales', ['limit' => $limit]);
    }

    public function getTopPerformersByTransactions(int $limit = 10): Promise
    {
        return $this->executeSelectQuery('store_members.get_top_by_transactions', ['limit' => $limit]);
    }

    public function startSession(string $memberUuid): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(
            'store_member_sessions.start',
            [
                'member_uuid' => $memberUuid,
                'clock_in_time' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'sales_this_session' => 0.0,
                'transactions_this_session' => 0,
                'activities_log' => json_encode([])
            ]
        );
    }

    public function endSession(string $memberUuid, float $sales, int $transactions): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(
            'store_member_sessions.end',
            [
                'member_uuid' => $memberUuid,
                'clock_out_time' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'sales_this_session' => $sales,
                'transactions_this_session' => $transactions
            ]
        );
    }

    public function getActiveSession(string $memberUuid): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function () use ($resolver, $memberUuid) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect(
                    'store_member_sessions.get_active',
                    ['member_uuid' => $memberUuid]
                );

                if (empty($data)) {
                    $resolver->resolve(null);
                    return;
                }

                $sessionData = $data[0];
                $session = $this->mapRowToStoreMemberSession($sessionData);
                $resolver->resolve($session);
            } catch (\Throwable $e) {
                $resolver->reject($e);
            }
        });

        return $resolver->getPromise();
    }

    public function getAllActiveSessions(): Promise
    {
        return $this->executeSessionSelectQuery('store_member_sessions.get_all_active', []);
    }

    public function getMemberSessionHistory(string $memberUuid, int $limit = 50): Promise
    {
        return $this->executeSessionSelectQuery(
            'store_member_sessions.get_history',
            ['member_uuid' => $memberUuid, 'limit' => $limit]
        );
    }

    private function executeSelectQuery(string $query, array $params): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function () use ($resolver, $query, $params) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect($query, $params);
                $members = array_map([$this, 'mapRowToStoreMember'], $data);
                $resolver->resolve($members);
            } catch (\Throwable $e) {
                $resolver->reject($e);
            }
        });

        return $resolver->getPromise();
    }

    private function executeSessionSelectQuery(string $query, array $params): Promise
    {
        $resolver = new PromiseResolver();

        Await::f2c(function () use ($resolver, $query, $params) {
            try {
                $data = yield from $this->main->getDatabaseManager()->asyncSelect($query, $params);
                $sessions = array_map([$this, 'mapRowToStoreMemberSession'], $data);
                $resolver->resolve($sessions);
            } catch (\Throwable $e) {
                $resolver->reject($e);
            }
        });

        return $resolver->getPromise();
    }

    private function mapRowToStoreMember(array $row): StoreMember
    {
        return new StoreMember(
            uuid: $row['uuid'],
            username: $row['username'],
            role: StoreMemberRole::from($row['role']),
            status: StoreMemberStatus::from($row['status']),
            hiredAt: new DateTimeImmutable($row['hired_at']),
            lastLogin: $row['last_login'] ? new DateTimeImmutable($row['last_login']) : null,
            totalWorkingHours: (int)$row['total_working_hours'],
            totalTransactions: (int)$row['total_transactions'],
            totalSales: (float)$row['total_sales'],
            permissions: json_decode($row['permissions'] ?? '[]', true),
            storeId: $row['store_id'],
            lastPromotionAt: $row['last_promotion_at'] ? new DateTimeImmutable($row['last_promotion_at']) : null,
            notes: $row['notes']
        );
    }

    private function mapRowToStoreMemberSession(array $row): StoreMemberSession
    {
        return new StoreMemberSession(
            memberUuid: $row['member_uuid'],
            clockInTime: new DateTimeImmutable($row['clock_in_time']),
            clockOutTime: $row['clock_out_time'] ? new DateTimeImmutable($row['clock_out_time']) : null,
            salesThisSession: (float)$row['sales_this_session'],
            transactionsThisSession: (int)$row['transactions_this_session'],
            activitiesLog: json_decode($row['activities_log'] ?? '[]', true)
        );
    }
}