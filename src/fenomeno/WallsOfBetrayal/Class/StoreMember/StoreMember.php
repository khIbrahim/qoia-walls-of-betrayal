<?php

namespace fenomeno\WallsOfBetrayal\Class\StoreMember;

use DateTimeImmutable;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use JsonSerializable;
use pocketmine\utils\TextFormat;

final class StoreMember implements JsonSerializable
{
    public function __construct(
        private readonly string $uuid,
        private readonly string $username,
        private StoreMemberRole $role,
        private StoreMemberStatus $status,
        private readonly DateTimeImmutable $hiredAt,
        private ?DateTimeImmutable $lastLogin = null,
        private int $totalWorkingHours = 0,
        private int $totalTransactions = 0,
        private float $totalSales = 0.0,
        private array $permissions = [],
        private ?string $storeId = null,
        private ?DateTimeImmutable $lastPromotionAt = null,
        private ?string $notes = null
    ) {
        // Set default permissions based on role
        if (empty($this->permissions)) {
            $this->permissions = $this->role->getPermissions();
        }
    }

    // Getters
    public function getUuid(): string { return $this->uuid; }
    public function getUsername(): string { return $this->username; }
    public function getRole(): StoreMemberRole { return $this->role; }
    public function getStatus(): StoreMemberStatus { return $this->status; }
    public function getHiredAt(): DateTimeImmutable { return $this->hiredAt; }
    public function getLastLogin(): ?DateTimeImmutable { return $this->lastLogin; }
    public function getTotalWorkingHours(): int { return $this->totalWorkingHours; }
    public function getTotalTransactions(): int { return $this->totalTransactions; }
    public function getTotalSales(): float { return $this->totalSales; }
    public function getPermissions(): array { return $this->permissions; }
    public function getStoreId(): ?string { return $this->storeId; }
    public function getLastPromotionAt(): ?DateTimeImmutable { return $this->lastPromotionAt; }
    public function getNotes(): ?string { return $this->notes; }

    // Setters
    public function setRole(StoreMemberRole $role): void 
    { 
        $this->role = $role;
        $this->permissions = $role->getPermissions();
        $this->lastPromotionAt = new DateTimeImmutable();
    }

    public function setStatus(StoreMemberStatus $status): void { $this->status = $status; }
    public function setLastLogin(DateTimeImmutable $lastLogin): void { $this->lastLogin = $lastLogin; }
    public function setTotalWorkingHours(int $hours): void { $this->totalWorkingHours = $hours; }
    public function addWorkingHours(int $hours): void { $this->totalWorkingHours += $hours; }
    public function setTotalTransactions(int $transactions): void { $this->totalTransactions = $transactions; }
    public function incrementTransactions(): void { $this->totalTransactions++; }
    public function setTotalSales(float $sales): void { $this->totalSales = $sales; }
    public function addSales(float $amount): void { $this->totalSales += $amount; }
    public function setPermissions(array $permissions): void { $this->permissions = $permissions; }
    public function setStoreId(?string $storeId): void { $this->storeId = $storeId; }
    public function setNotes(?string $notes): void { $this->notes = $notes; }

    // Permission methods
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(string $permission): void
    {
        $this->permissions = array_filter(
            $this->permissions, 
            fn($perm) => $perm !== $permission
        );
        $this->permissions = array_values($this->permissions);
    }

    // Business logic methods
    public function canManage(StoreMember $other): bool
    {
        return $this->role->canManage($other->role);
    }

    public function isActive(): bool
    {
        return $this->status->canWork();
    }

    public function getWorkingDays(): int
    {
        $now = new DateTimeImmutable();
        return $now->diff($this->hiredAt)->days;
    }

    public function getAverageSalesPerTransaction(): float
    {
        return $this->totalTransactions > 0 ? $this->totalSales / $this->totalTransactions : 0.0;
    }

    public function getDisplayName(): string
    {
        $statusColor = $this->status->getColor();
        $roleDisplay = $this->role->getDisplayName();
        
        return "{$statusColor}{$this->username}§r §7({$roleDisplay})§r";
    }

    public function getFormattedInfo(): array
    {
        return [
            "§bInformations du membre:",
            "§7Nom: §f{$this->username}",
            "§7UUID: §f{$this->uuid}",
            "§7Rôle: §f{$this->role->getDisplayName()}",
            "§7Statut: {$this->status->getColor()}{$this->status->getDisplayName()}",
            "§7Embauché le: §f" . $this->hiredAt->format('d/m/Y'),
            "§7Heures travaillées: §f{$this->totalWorkingHours}h",
            "§7Transactions: §f{$this->totalTransactions}",
            "§7Ventes totales: §f{$this->totalSales}€",
            "§7Jours de service: §f{$this->getWorkingDays()}",
            $this->lastLogin ? "§7Dernière connexion: §f" . $this->lastLogin->format('d/m/Y H:i') : "§7Dernière connexion: §cJamais"
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'username' => $this->username,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'hired_at' => $this->hiredAt->format('Y-m-d H:i:s'),
            'last_login' => $this->lastLogin?->format('Y-m-d H:i:s'),
            'total_working_hours' => $this->totalWorkingHours,
            'total_transactions' => $this->totalTransactions,
            'total_sales' => $this->totalSales,
            'permissions' => $this->permissions,
            'store_id' => $this->storeId,
            'last_promotion_at' => $this->lastPromotionAt?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'working_days' => $this->getWorkingDays(),
            'average_sales_per_transaction' => $this->getAverageSalesPerTransaction()
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            username: $data['username'],
            role: StoreMemberRole::from($data['role']),
            status: StoreMemberStatus::from($data['status']),
            hiredAt: new DateTimeImmutable($data['hired_at']),
            lastLogin: isset($data['last_login']) ? new DateTimeImmutable($data['last_login']) : null,
            totalWorkingHours: $data['total_working_hours'] ?? 0,
            totalTransactions: $data['total_transactions'] ?? 0,
            totalSales: $data['total_sales'] ?? 0.0,
            permissions: $data['permissions'] ?? [],
            storeId: $data['store_id'] ?? null,
            lastPromotionAt: isset($data['last_promotion_at']) ? new DateTimeImmutable($data['last_promotion_at']) : null,
            notes: $data['notes'] ?? null
        );
    }
}