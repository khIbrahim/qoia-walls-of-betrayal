<?php

namespace fenomeno\WallsOfBetrayal\Class\Store;

use JsonSerializable;

final class StoreMemberShift implements JsonSerializable {

    public function __construct(
        private readonly int $id,
        private readonly string $memberUuid,
        private readonly string $storeId,
        private int $shiftStart,
        private ?int $shiftEnd = null,
        private int $breakDuration = 0, // in minutes
        private float $salesDuringShift = 0.0,
        private float $commissionEarned = 0.0,
        private string $notes = '',
        private string $status = 'active', // active, completed, cancelled
        private int $createdAt = 0
    ) {
        if ($this->createdAt === 0) {
            $this->createdAt = time();
        }
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getMemberUuid(): string { return $this->memberUuid; }
    public function getStoreId(): string { return $this->storeId; }
    public function getShiftStart(): int { return $this->shiftStart; }
    public function getShiftEnd(): ?int { return $this->shiftEnd; }
    public function getBreakDuration(): int { return $this->breakDuration; }
    public function getSalesDuringShift(): float { return $this->salesDuringShift; }
    public function getCommissionEarned(): float { return $this->commissionEarned; }
    public function getNotes(): string { return $this->notes; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): int { return $this->createdAt; }

    // Setters
    public function endShift(?int $endTime = null): void {
        $this->shiftEnd = $endTime ?? time();
        $this->status = 'completed';
    }

    public function cancelShift(): void {
        $this->status = 'cancelled';
    }

    public function addBreakTime(int $minutes): void {
        $this->breakDuration += $minutes;
    }

    public function addSale(float $saleAmount, float $commissionAmount): void {
        $this->salesDuringShift += $saleAmount;
        $this->commissionEarned += $commissionAmount;
    }

    public function setNotes(string $notes): void {
        $this->notes = $notes;
    }

    // Utility methods
    public function isActive(): bool {
        return $this->status === 'active';
    }

    public function isCompleted(): bool {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool {
        return $this->status === 'cancelled';
    }

    public function getDuration(): int {
        if ($this->shiftEnd === null) {
            return time() - $this->shiftStart;
        }
        return $this->shiftEnd - $this->shiftStart;
    }

    public function getWorkingDuration(): int {
        $totalDuration = $this->getDuration();
        $breakTime = $this->breakDuration * 60; // convert minutes to seconds
        return max(0, $totalDuration - $breakTime);
    }

    public function getHoursWorked(): float {
        return $this->getWorkingDuration() / 3600;
    }

    public function getAverageSalePerHour(): float {
        $hoursWorked = $this->getHoursWorked();
        return $hoursWorked > 0 ? $this->salesDuringShift / $hoursWorked : 0;
    }

    public function getFormattedDuration(): string {
        $duration = $this->getDuration();
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'member_uuid' => $this->memberUuid,
            'store_id' => $this->storeId,
            'shift_start' => $this->shiftStart,
            'shift_start_formatted' => date('Y-m-d H:i:s', $this->shiftStart),
            'shift_end' => $this->shiftEnd,
            'shift_end_formatted' => $this->shiftEnd ? date('Y-m-d H:i:s', $this->shiftEnd) : null,
            'break_duration' => $this->breakDuration,
            'break_duration_formatted' => sprintf('%02d:%02d', floor($this->breakDuration / 60), $this->breakDuration % 60),
            'sales_during_shift' => $this->salesDuringShift,
            'commission_earned' => $this->commissionEarned,
            'notes' => $this->notes,
            'status' => $this->status,
            'duration_seconds' => $this->getDuration(),
            'duration_formatted' => $this->getFormattedDuration(),
            'working_duration_seconds' => $this->getWorkingDuration(),
            'hours_worked' => $this->getHoursWorked(),
            'average_sale_per_hour' => $this->getAverageSalePerHour(),
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_cancelled' => $this->isCancelled(),
            'created_at' => $this->createdAt
        ];
    }

    public static function fromDatabaseRow(array $row): self {
        return new self(
            id: (int)$row['id'],
            memberUuid: $row['member_uuid'],
            storeId: $row['store_id'],
            shiftStart: strtotime($row['shift_start']),
            shiftEnd: $row['shift_end'] ? strtotime($row['shift_end']) : null,
            breakDuration: (int)$row['break_duration'],
            salesDuringShift: (float)$row['sales_during_shift'],
            commissionEarned: (float)$row['commission_earned'],
            notes: $row['notes'] ?? '',
            status: $row['status'],
            createdAt: strtotime($row['created_at'])
        );
    }
}