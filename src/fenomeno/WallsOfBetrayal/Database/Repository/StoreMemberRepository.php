<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Class\Store\StoreMember;
use fenomeno\WallsOfBetrayal\Class\Store\StoreMemberShift;
use fenomeno\WallsOfBetrayal\Class\Store\StoreTransaction;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\StoreMemberRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Main;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Generator;

final class StoreMemberRepository implements StoreMemberRepositoryInterface {

    public function __construct(
        private readonly Main $main,
        private readonly DataConnector $database
    ) {}

    public function createMember(
        string $uuid,
        string $username,
        string $storeId,
        StoreMemberRole $role,
        array $permissions = [],
        float $salary = 0.0,
        float $commissionRate = 0.0
    ): Await {
        return Await::f2c(function() use ($uuid, $username, $storeId, $role, $permissions, $salary, $commissionRate): Generator {
            yield from $this->database->asyncInsert("store_members.create", [
                "uuid" => $uuid,
                "username" => $username,
                "store_id" => $storeId,
                "role" => $role->value,
                "permissions" => json_encode($permissions),
                "salary" => $salary,
                "commission_rate" => $commissionRate
            ]);
            
            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function getMember(string $uuid, string $storeId): Await {
        return Await::f2c(function() use ($uuid, $storeId): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.get", [
                "uuid" => $uuid,
                "store_id" => $storeId
            ]);

            if (empty($rows)) {
                return null;
            }

            return StoreMember::fromDatabaseRow($rows[0]);
        });
    }

    public function getStoreMembers(string $storeId, int $limit = 20, int $offset = 0): Await {
        return Await::f2c(function() use ($storeId, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.getByStore", [
                "store_id" => $storeId,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return array_map(fn(array $row) => StoreMember::fromDatabaseRow($row), $rows);
        });
    }

    public function getActiveStoreMembers(string $storeId): Await {
        return Await::f2c(function() use ($storeId): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.getActiveByStore", [
                "store_id" => $storeId
            ]);

            return array_map(fn(array $row) => StoreMember::fromDatabaseRow($row), $rows);
        });
    }

    public function updateMemberRole(string $uuid, string $storeId, StoreMemberRole $role, array $permissions): Await {
        return Await::f2c(function() use ($uuid, $storeId, $role, $permissions): Generator {
            yield from $this->database->asyncChange("store_members.updateRole", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "role" => $role->value,
                "permissions" => json_encode($permissions)
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function updateMemberSalary(string $uuid, string $storeId, float $salary, float $commissionRate): Await {
        return Await::f2c(function() use ($uuid, $storeId, $salary, $commissionRate): Generator {
            yield from $this->database->asyncChange("store_members.updateSalary", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "salary" => $salary,
                "commission_rate" => $commissionRate
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function setMemberActivity(string $uuid, string $storeId, bool $isActive): Await {
        return Await::f2c(function() use ($uuid, $storeId, $isActive): Generator {
            yield from $this->database->asyncChange("store_members.updateActivity", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "is_active" => $isActive
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function addMemberSale(string $uuid, string $storeId, float $saleAmount, float $commissionAmount): Await {
        return Await::f2c(function() use ($uuid, $storeId, $saleAmount, $commissionAmount): Generator {
            yield from $this->database->asyncChange("store_members.addSale", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "sale_amount" => $saleAmount,
                "commission_amount" => $commissionAmount
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function startMemberShift(string $uuid, string $storeId, int $startTime): Await {
        return Await::f2c(function() use ($uuid, $storeId, $startTime): Generator {
            yield from $this->database->asyncChange("store_members.startShift", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "start_time" => $startTime
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function endMemberShift(string $uuid, string $storeId, int $endTime): Await {
        return Await::f2c(function() use ($uuid, $storeId, $endTime): Generator {
            yield from $this->database->asyncChange("store_members.endShift", [
                "uuid" => $uuid,
                "store_id" => $storeId,
                "end_time" => $endTime
            ]);

            return yield from $this->getMember($uuid, $storeId);
        });
    }

    public function getTopSellers(string $storeId, int $limit = 10): Await {
        return Await::f2c(function() use ($storeId, $limit): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.getTopSellers", [
                "store_id" => $storeId,
                "limit" => $limit
            ]);

            return $rows;
        });
    }

    public function searchMembers(string $storeId, string $searchTerm, int $limit = 20, int $offset = 0): Await {
        return Await::f2c(function() use ($storeId, $searchTerm, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.searchMembers", [
                "store_id" => $storeId,
                "search_term" => $searchTerm,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return array_map(fn(array $row) => StoreMember::fromDatabaseRow($row), $rows);
        });
    }

    public function deleteMember(string $uuid, string $storeId): Await {
        return Await::f2c(function() use ($uuid, $storeId): Generator {
            $member = yield from $this->getMember($uuid, $storeId);
            
            yield from $this->database->asyncChange("store_members.delete", [
                "uuid" => $uuid,
                "store_id" => $storeId
            ]);

            return $member;
        });
    }

    public function getStoreStats(string $storeId): Await {
        return Await::f2c(function() use ($storeId): Generator {
            $rows = yield from $this->database->asyncSelect("store_members.getStoreStats", [
                "store_id" => $storeId
            ]);

            return $rows[0] ?? null;
        });
    }

    // Shift Management Methods
    public function startShift(string $memberUuid, string $storeId, int $shiftStart, string $notes = ''): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $shiftStart, $notes): Generator {
            yield from $this->database->asyncInsert("store_member_shifts.startShift", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId,
                "shift_start" => $shiftStart,
                "notes" => $notes
            ]);

            return yield from $this->getActiveShift($memberUuid, $storeId);
        });
    }

    public function endShift(int $shiftId, int $shiftEnd, int $breakDuration, float $salesDuringShift, float $commissionEarned): Await {
        return Await::f2c(function() use ($shiftId, $shiftEnd, $breakDuration, $salesDuringShift, $commissionEarned): Generator {
            yield from $this->database->asyncChange("store_member_shifts.endShift", [
                "id" => $shiftId,
                "shift_end" => $shiftEnd,
                "break_duration" => $breakDuration,
                "sales_during_shift" => $salesDuringShift,
                "commission_earned" => $commissionEarned
            ]);

            return true;
        });
    }

    public function getActiveShift(string $memberUuid, string $storeId): Await {
        return Await::f2c(function() use ($memberUuid, $storeId): Generator {
            $rows = yield from $this->database->asyncSelect("store_member_shifts.getActiveShift", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId
            ]);

            if (empty($rows)) {
                return null;
            }

            return StoreMemberShift::fromDatabaseRow($rows[0]);
        });
    }

    public function getMemberShifts(string $memberUuid, string $storeId, int $limit = 20, int $offset = 0): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_member_shifts.getMemberShifts", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return array_map(fn(array $row) => StoreMemberShift::fromDatabaseRow($row), $rows);
        });
    }

    public function getStoreShifts(string $storeId, int $startDate, int $endDate, int $limit = 50, int $offset = 0): Await {
        return Await::f2c(function() use ($storeId, $startDate, $endDate, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_member_shifts.getStoreShifts", [
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return $rows;
        });
    }

    public function getShiftStats(string $memberUuid, string $storeId, int $startDate, int $endDate): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $startDate, $endDate): Generator {
            $rows = yield from $this->database->asyncSelect("store_member_shifts.getShiftStats", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate
            ]);

            return $rows[0] ?? null;
        });
    }

    public function cancelShift(int $shiftId): Await {
        return Await::f2c(function() use ($shiftId): Generator {
            yield from $this->database->asyncChange("store_member_shifts.cancelShift", [
                "id" => $shiftId
            ]);

            return true;
        });
    }

    public function updateShiftSales(int $shiftId, float $salesAmount, float $commissionAmount): Await {
        return Await::f2c(function() use ($shiftId, $salesAmount, $commissionAmount): Generator {
            yield from $this->database->asyncChange("store_member_shifts.updateShiftSales", [
                "id" => $shiftId,
                "sales_amount" => $salesAmount,
                "commission_amount" => $commissionAmount
            ]);

            return true;
        });
    }

    // Transaction Management Methods
    public function createTransaction(
        string $transactionUuid,
        string $storeId,
        string $memberUuid,
        ?string $customerUuid,
        string $transactionType,
        float $totalAmount,
        float $commissionAmount,
        array $itemsSold,
        string $paymentMethod,
        ?int $shiftId = null,
        string $notes = ''
    ): Await {
        return Await::f2c(function() use (
            $transactionUuid, $storeId, $memberUuid, $customerUuid,
            $transactionType, $totalAmount, $commissionAmount, $itemsSold,
            $paymentMethod, $shiftId, $notes
        ): Generator {
            yield from $this->database->asyncInsert("store_transactions.createTransaction", [
                "transaction_uuid" => $transactionUuid,
                "store_id" => $storeId,
                "member_uuid" => $memberUuid,
                "customer_uuid" => $customerUuid,
                "transaction_type" => $transactionType,
                "total_amount" => $totalAmount,
                "commission_amount" => $commissionAmount,
                "items_sold" => json_encode($itemsSold),
                "payment_method" => $paymentMethod,
                "shift_id" => $shiftId,
                "notes" => $notes
            ]);

            return yield from $this->getTransaction($transactionUuid);
        });
    }

    public function getTransaction(string $transactionUuid): Await {
        return Await::f2c(function() use ($transactionUuid): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getTransaction", [
                "transaction_uuid" => $transactionUuid
            ]);

            if (empty($rows)) {
                return null;
            }

            return StoreTransaction::fromDatabaseRow($rows[0]);
        });
    }

    public function getMemberTransactions(string $memberUuid, string $storeId, int $limit = 20, int $offset = 0): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getMemberTransactions", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return array_map(fn(array $row) => StoreTransaction::fromDatabaseRow($row), $rows);
        });
    }

    public function getStoreTransactions(string $storeId, int $startDate, int $endDate, int $limit = 50, int $offset = 0): Await {
        return Await::f2c(function() use ($storeId, $startDate, $endDate, $limit, $offset): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getStoreTransactions", [
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "limit" => $limit,
                "offset" => $offset
            ]);

            return $rows;
        });
    }

    public function getTransactionsByShift(int $shiftId): Await {
        return Await::f2c(function() use ($shiftId): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getTransactionsByShift", [
                "shift_id" => $shiftId
            ]);

            return array_map(fn(array $row) => StoreTransaction::fromDatabaseRow($row), $rows);
        });
    }

    public function getMemberSalesStats(string $memberUuid, string $storeId, int $startDate, int $endDate): Await {
        return Await::f2c(function() use ($memberUuid, $storeId, $startDate, $endDate): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getMemberSalesStats", [
                "member_uuid" => $memberUuid,
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate
            ]);

            return $rows[0] ?? null;
        });
    }

    public function getStoreSalesStats(string $storeId, int $startDate, int $endDate): Await {
        return Await::f2c(function() use ($storeId, $startDate, $endDate): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getStoreSalesStats", [
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate
            ]);

            return $rows[0] ?? null;
        });
    }

    public function getTopSellingItems(string $storeId, int $startDate, int $endDate, int $limit = 10): Await {
        return Await::f2c(function() use ($storeId, $startDate, $endDate, $limit): Generator {
            $rows = yield from $this->database->asyncSelect("store_transactions.getTopSellingItems", [
                "store_id" => $storeId,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "limit" => $limit
            ]);

            return $rows;
        });
    }

    public function voidTransaction(string $transactionUuid): Await {
        return Await::f2c(function() use ($transactionUuid): Generator {
            yield from $this->database->asyncChange("store_transactions.voidTransaction", [
                "transaction_uuid" => $transactionUuid
            ]);

            return yield from $this->getTransaction($transactionUuid);
        });
    }
}