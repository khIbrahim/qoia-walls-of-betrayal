<?php

namespace fenomeno\WallsOfBetrayal\Class\Store;

use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\Store\StoreMemberStatus;

/**
 * Represents a store/restaurant in the POS ecosystem
 * Each store can have multiple members with different roles
 */
final class Store {

    private string $id;
    private string $name;
    private string $description;
    private string $address;
    private string $phone;
    private string $email;
    private array $settings;
    private array $metadata;
    private int $createdAt;
    private int $updatedAt;
    private bool $isActive;

    /** @var array<string, StoreMember> */
    private array $members = [];

    public function __construct(
        string $name,
        string $description = '',
        string $address = '',
        string $phone = '',
        string $email = '',
        array $settings = [],
        array $metadata = [],
        bool $isActive = true
    ) {
        $this->id = $this->generateId();
        $this->name = $name;
        $this->description = $description;
        $this->address = $address;
        $this->phone = $phone;
        $this->email = $email;
        $this->settings = $settings;
        $this->metadata = $metadata;
        $this->isActive = $isActive;
        
        $this->createdAt = time();
        $this->updatedAt = time();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getAddress(): string { return $this->address; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): string { return $this->email; }
    public function getSettings(): array { return $this->settings; }
    public function getMetadata(): array { return $this->metadata; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getUpdatedAt(): int { return $this->updatedAt; }
    public function isActive(): bool { return $this->isActive; }

    /** @return array<string, StoreMember> */
    public function getMembers(): array { return $this->members; }

    // Setters with validation
    public function setName(string $name): void {
        if (strlen(trim($name)) < 1) {
            throw new \InvalidArgumentException("Store name cannot be empty");
        }
        $this->name = trim($name);
        $this->updateTimestamp();
    }

    public function setDescription(string $description): void {
        $this->description = trim($description);
        $this->updateTimestamp();
    }

    public function setAddress(string $address): void {
        $this->address = trim($address);
        $this->updateTimestamp();
    }

    public function setPhone(string $phone): void {
        $cleanPhone = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
        $this->phone = $cleanPhone;
        $this->updateTimestamp();
    }

    public function setEmail(string $email): void {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format");
        }
        $this->email = strtolower(trim($email));
        $this->updateTimestamp();
    }

    public function setSettings(array $settings): void {
        $this->settings = $settings;
        $this->updateTimestamp();
    }

    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
        $this->updateTimestamp();
    }

    public function setActive(bool $isActive): void {
        $this->isActive = $isActive;
        $this->updateTimestamp();
    }

    // Member management
    public function addMember(StoreMember $member): void {
        if ($this->hasMember($member->getId())) {
            throw new \InvalidArgumentException("Member already exists in this store");
        }
        
        $this->members[$member->getId()] = $member;
        $this->updateTimestamp();
    }

    public function removeMember(string $memberId): bool {
        if (!$this->hasMember($memberId)) {
            return false;
        }
        
        unset($this->members[$memberId]);
        $this->updateTimestamp();
        return true;
    }

    public function getMember(string $memberId): ?StoreMember {
        return $this->members[$memberId] ?? null;
    }

    public function hasMember(string $memberId): bool {
        return isset($this->members[$memberId]);
    }

    public function updateMember(StoreMember $member): void {
        if (!$this->hasMember($member->getId())) {
            throw new \InvalidArgumentException("Member does not exist in this store");
        }
        
        $this->members[$member->getId()] = $member;
        $this->updateTimestamp();
    }

    // Member queries
    public function getMembersByRole(StoreMemberRole $role): array {
        return array_filter(
            $this->members,
            fn(StoreMember $member) => $member->getRole() === $role
        );
    }

    public function getMembersByStatus(StoreMemberStatus $status): array {
        return array_filter(
            $this->members,
            fn(StoreMember $member) => $member->getStatus() === $status
        );
    }

    public function getActiveMembers(): array {
        return $this->getMembersByStatus(StoreMemberStatus::ACTIVE);
    }

    public function getManagers(): array {
        return $this->getMembersByRole(StoreMemberRole::MANAGER);
    }

    public function getSupervisors(): array {
        return $this->getMembersByRole(StoreMemberRole::SUPERVISOR);
    }

    public function getCashiers(): array {
        return $this->getMembersByRole(StoreMemberRole::CASHIER);
    }

    public function getEmployees(): array {
        return $this->getMembersByRole(StoreMemberRole::EMPLOYEE);
    }

    public function getMemberByEmail(string $email): ?StoreMember {
        foreach ($this->members as $member) {
            if (strtolower($member->getEmail()) === strtolower($email)) {
                return $member;
            }
        }
        return null;
    }

    public function getMemberCount(): int {
        return count($this->members);
    }

    public function getActiveMemberCount(): int {
        return count($this->getActiveMembers());
    }

    // Settings management
    public function getSetting(string $key, mixed $default = null): mixed {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void {
        $this->settings[$key] = $value;
        $this->updateTimestamp();
    }

    public function removeSetting(string $key): void {
        unset($this->settings[$key]);
        $this->updateTimestamp();
    }

    public function hasSetting(string $key): bool {
        return isset($this->settings[$key]);
    }

    // Metadata management
    public function getMetadataValue(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function setMetadataValue(string $key, mixed $value): void {
        $this->metadata[$key] = $value;
        $this->updateTimestamp();
    }

    public function removeMetadataValue(string $key): void {
        unset($this->metadata[$key]);
        $this->updateTimestamp();
    }

    public function hasMetadataValue(string $key): bool {
        return isset($this->metadata[$key]);
    }

    // Serialization
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'isActive' => $this->isActive,
            'members' => array_map(
                fn(StoreMember $member) => $member->toArray(),
                $this->members
            ),
            'memberCount' => $this->getMemberCount(),
            'activeMemberCount' => $this->getActiveMemberCount()
        ];
    }

    public function toJson(): string {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public static function fromArray(array $data): self {
        $store = new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            address: $data['address'] ?? '',
            phone: $data['phone'] ?? '',
            email: $data['email'] ?? '',
            settings: $data['settings'] ?? [],
            metadata: $data['metadata'] ?? [],
            isActive: $data['isActive'] ?? true
        );

        // Set timestamps if provided
        if (isset($data['createdAt'])) {
            $store->createdAt = $data['createdAt'];
        }
        if (isset($data['updatedAt'])) {
            $store->updatedAt = $data['updatedAt'];
        }
        if (isset($data['id'])) {
            $store->id = $data['id'];
        }

        // Load members if provided
        if (isset($data['members']) && is_array($data['members'])) {
            foreach ($data['members'] as $memberData) {
                if (is_array($memberData)) {
                    $member = StoreMember::fromArray($memberData);
                    $store->members[$member->getId()] = $member;
                }
            }
        }

        return $store;
    }

    // Private methods
    private function generateId(): string {
        return 'store_' . uniqid() . '_' . random_int(1000, 9999);
    }

    private function updateTimestamp(): void {
        $this->updatedAt = time();
    }
}