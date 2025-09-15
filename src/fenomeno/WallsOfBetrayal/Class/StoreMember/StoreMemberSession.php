<?php

namespace fenomeno\WallsOfBetrayal\Class\StoreMember;

use DateTimeImmutable;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use JsonSerializable;

final class StoreMemberSession implements JsonSerializable
{
    public function __construct(
        private readonly string $memberUuid,
        private readonly DateTimeImmutable $clockInTime,
        private ?DateTimeImmutable $clockOutTime = null,
        private float $salesThisSession = 0.0,
        private int $transactionsThisSession = 0,
        private array $activitiesLog = []
    ) {}

    // Getters
    public function getMemberUuid(): string { return $this->memberUuid; }
    public function getClockInTime(): DateTimeImmutable { return $this->clockInTime; }
    public function getClockOutTime(): ?DateTimeImmutable { return $this->clockOutTime; }
    public function getSalesThisSession(): float { return $this->salesThisSession; }
    public function getTransactionsThisSession(): int { return $this->transactionsThisSession; }
    public function getActivitiesLog(): array { return $this->activitiesLog; }

    // Session management
    public function clockOut(): void
    {
        $this->clockOutTime = new DateTimeImmutable();
        $this->logActivity('clock_out', 'Fin de session');
    }

    public function isActive(): bool
    {
        return $this->clockOutTime === null;
    }

    public function getSessionDuration(): int
    {
        $endTime = $this->clockOutTime ?? new DateTimeImmutable();
        return $endTime->getTimestamp() - $this->clockInTime->getTimestamp();
    }

    public function getSessionDurationHours(): float
    {
        return $this->getSessionDuration() / 3600;
    }

    // Sales tracking
    public function addSale(float $amount): void
    {
        $this->salesThisSession += $amount;
        $this->transactionsThisSession++;
        $this->logActivity('sale', "Vente: {$amount}€");
    }

    public function getAverageSaleThisSession(): float
    {
        return $this->transactionsThisSession > 0 
            ? $this->salesThisSession / $this->transactionsThisSession 
            : 0.0;
    }

    // Activity logging
    public function logActivity(string $type, string $description): void
    {
        $this->activitiesLog[] = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'type' => $type,
            'description' => $description
        ];
    }

    public function getFormattedSessionInfo(): array
    {
        $duration = $this->getSessionDurationHours();
        $status = $this->isActive() ? '§aActive' : '§cTerminée';
        
        return [
            "§bSession de travail:",
            "§7Début: §f" . $this->clockInTime->format('d/m/Y H:i'),
            $this->clockOutTime ? "§7Fin: §f" . $this->clockOutTime->format('d/m/Y H:i') : "§7Fin: §eEn cours",
            "§7Durée: §f" . number_format($duration, 2) . "h",
            "§7Statut: {$status}",
            "§7Ventes: §f{$this->salesThisSession}€",
            "§7Transactions: §f{$this->transactionsThisSession}",
            "§7Moyenne/transaction: §f" . number_format($this->getAverageSaleThisSession(), 2) . "€"
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'member_uuid' => $this->memberUuid,
            'clock_in_time' => $this->clockInTime->format('Y-m-d H:i:s'),
            'clock_out_time' => $this->clockOutTime?->format('Y-m-d H:i:s'),
            'sales_this_session' => $this->salesThisSession,
            'transactions_this_session' => $this->transactionsThisSession,
            'activities_log' => $this->activitiesLog,
            'session_duration_hours' => $this->getSessionDurationHours(),
            'average_sale_this_session' => $this->getAverageSaleThisSession(),
            'is_active' => $this->isActive()
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            memberUuid: $data['member_uuid'],
            clockInTime: new DateTimeImmutable($data['clock_in_time']),
            clockOutTime: isset($data['clock_out_time']) ? new DateTimeImmutable($data['clock_out_time']) : null,
            salesThisSession: $data['sales_this_session'] ?? 0.0,
            transactionsThisSession: $data['transactions_this_session'] ?? 0,
            activitiesLog: $data['activities_log'] ?? []
        );
    }
}