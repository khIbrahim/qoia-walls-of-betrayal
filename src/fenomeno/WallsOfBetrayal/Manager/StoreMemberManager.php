<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use DateTimeImmutable;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMember;
use fenomeno\WallsOfBetrayal\Class\StoreMember\StoreMemberSession;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\StoreMemberRepositoryInterface;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberRole;
use fenomeno\WallsOfBetrayal\Enum\StoreMemberStatus;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;
use WeakMap;

final class StoreMemberManager
{
    private StoreMemberRepositoryInterface $repository;
    private WeakMap $activeSessions;

    /** @var array<string, StoreMember> */
    private array $cachedMembers = [];

    public function __construct(private readonly Main $main)
    {
        $this->repository = $this->main->getDatabaseManager()->getStoreMemberRepository();
        $this->activeSessions = new WeakMap();
    }

    /**
     * Get a store member by UUID
     */
    public function getMemberByUuid(string $uuid): Generator
    {
        if (isset($this->cachedMembers[$uuid])) {
            return $this->cachedMembers[$uuid];
        }

        $member = yield from Await::promise($this->repository->loadByUuid($uuid));
        if ($member) {
            $this->cachedMembers[$uuid] = $member;
        }
        return $member;
    }

    /**
     * Get a store member by username
     */
    public function getMemberByUsername(string $username): Generator
    {
        $member = yield from Await::promise($this->repository->loadByUsername($username));
        if ($member) {
            $this->cachedMembers[$member->getUuid()] = $member;
        }
        return $member;
    }

    /**
     * Get a store member by Player object
     */
    public function getMemberByPlayer(Player $player): Generator
    {
        return yield from $this->getMemberByUuid($player->getUniqueId()->toString());
    }

    /**
     * Create a new store member
     */
    public function createMember(
        string $uuid,
        string $username,
        StoreMemberRole $role = StoreMemberRole::TRAINEE,
        ?string $storeId = null,
        ?string $notes = null
    ): Generator {
        $member = new StoreMember(
            uuid: $uuid,
            username: $username,
            role: $role,
            status: StoreMemberStatus::ACTIVE,
            hiredAt: new DateTimeImmutable(),
            storeId: $storeId,
            notes: $notes
        );

        yield from $this->repository->insert($member);
        $this->cachedMembers[$uuid] = $member;

        return $member;
    }

    /**
     * Update a store member
     */
    public function updateMember(StoreMember $member): Generator
    {
        yield from $this->repository->update($member);
        $this->cachedMembers[$member->getUuid()] = $member;
    }

    /**
     * Delete a store member
     */
    public function deleteMember(string $uuid): Generator
    {
        yield from $this->repository->delete($uuid);
        unset($this->cachedMembers[$uuid]);
    }

    /**
     * Promote a member to a higher role
     */
    public function promoteMember(StoreMember $member, StoreMemberRole $newRole): Generator
    {
        if ($newRole->getLevel() <= $member->getRole()->getLevel()) {
            throw new \InvalidArgumentException("Cannot promote to a lower or same role level");
        }

        $member->setRole($newRole);
        yield from $this->updateMember($member);
    }

    /**
     * Demote a member to a lower role
     */
    public function demoteMember(StoreMember $member, StoreMemberRole $newRole): Generator
    {
        if ($newRole->getLevel() >= $member->getRole()->getLevel()) {
            throw new \InvalidArgumentException("Cannot demote to a higher or same role level");
        }

        $member->setRole($newRole);
        yield from $this->updateMember($member);
    }

    /**
     * Change member status
     */
    public function changeMemberStatus(StoreMember $member, StoreMemberStatus $status): Generator
    {
        $member->setStatus($status);
        yield from $this->updateMember($member);
    }

    /**
     * Clock in a member (start work session)
     */
    public function clockIn(Player $player): Generator
    {
        $member = yield from $this->getMemberByPlayer($player);
        if (!$member) {
            throw new \InvalidArgumentException("Player is not a store member");
        }

        if (!$member->isActive()) {
            throw new \InvalidArgumentException("Member is not active and cannot work");
        }

        // Check if already clocked in
        $activeSession = yield from Await::promise($this->repository->getActiveSession($member->getUuid()));
        if ($activeSession) {
            throw new \InvalidArgumentException("Member is already clocked in");
        }

        // Start new session
        yield from $this->repository->startSession($member->getUuid());
        yield from $this->repository->updateLastLogin($member->getUuid());

        $member->setLastLogin(new DateTimeImmutable());
        $this->cachedMembers[$member->getUuid()] = $member;

        return true;
    }

    /**
     * Clock out a member (end work session)
     */
    public function clockOut(Player $player): Generator
    {
        $member = yield from $this->getMemberByPlayer($player);
        if (!$member) {
            throw new \InvalidArgumentException("Player is not a store member");
        }

        $activeSession = yield from Await::promise($this->repository->getActiveSession($member->getUuid()));
        if (!$activeSession) {
            throw new \InvalidArgumentException("Member is not clocked in");
        }

        // End session
        yield from $this->repository->endSession(
            $member->getUuid(),
            $activeSession->getSalesThisSession(),
            $activeSession->getTransactionsThisSession()
        );

        // Update member's total stats
        $sessionHours = (int)ceil($activeSession->getSessionDurationHours());
        yield from $this->repository->updateWorkStats(
            $member->getUuid(),
            $sessionHours,
            $activeSession->getSalesThisSession(),
            $activeSession->getTransactionsThisSession()
        );

        // Update cached member
        $member->addWorkingHours($sessionHours);
        $member->addSales($activeSession->getSalesThisSession());
        $member->setTotalTransactions($member->getTotalTransactions() + $activeSession->getTransactionsThisSession());
        $this->cachedMembers[$member->getUuid()] = $member;

        return $activeSession;
    }

    /**
     * Add a sale for a member during their active session
     */
    public function addSale(Player $player, float $amount): Generator
    {
        $member = yield from $this->getMemberByPlayer($player);
        if (!$member) {
            throw new \InvalidArgumentException("Player is not a store member");
        }

        $activeSession = yield from Await::promise($this->repository->getActiveSession($member->getUuid()));
        if (!$activeSession) {
            throw new \InvalidArgumentException("Member is not clocked in");
        }

        $activeSession->addSale($amount);
        
        // Update session in database would be done here
        // For now, we'll store it in memory and update when clocking out

        return true;
    }

    /**
     * Get all store members
     */
    public function getAllMembers(): Generator
    {
        return yield from Await::promise($this->repository->getAll());
    }

    /**
     * Get members by role
     */
    public function getMembersByRole(StoreMemberRole $role): Generator
    {
        return yield from Await::promise($this->repository->getByRole($role));
    }

    /**
     * Get members by status
     */
    public function getMembersByStatus(StoreMemberStatus $status): Generator
    {
        return yield from Await::promise($this->repository->getByStatus($status));
    }

    /**
     * Get top performers by sales
     */
    public function getTopPerformersBySales(int $limit = 10): Generator
    {
        return yield from Await::promise($this->repository->getTopPerformersBySales($limit));
    }

    /**
     * Get top performers by transactions
     */
    public function getTopPerformersByTransactions(int $limit = 10): Generator
    {
        return yield from Await::promise($this->repository->getTopPerformersByTransactions($limit));
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(): Generator
    {
        return yield from Await::promise($this->repository->getAllActiveSessions());
    }

    /**
     * Get member session history
     */
    public function getMemberSessionHistory(string $memberUuid, int $limit = 50): Generator
    {
        return yield from Await::promise($this->repository->getMemberSessionHistory($memberUuid, $limit));
    }

    /**
     * Check if a player can manage another member
     */
    public function canManage(Player $manager, StoreMember $target): Generator
    {
        $managerMember = yield from $this->getMemberByPlayer($manager);
        if (!$managerMember) {
            return false;
        }

        return $managerMember->canManage($target);
    }

    /**
     * Check if a player has a specific permission
     */
    public function hasPermission(Player $player, string $permission): Generator
    {
        $member = yield from $this->getMemberByPlayer($player);
        if (!$member) {
            return false;
        }

        return $member->hasPermission($permission);
    }

    /**
     * Check if a player is a store member
     */
    public function isStoreMember(Player $player): Generator
    {
        $member = yield from $this->getMemberByPlayer($player);
        return $member !== null && $member->isActive();
    }

    /**
     * Get cached member or null
     */
    public function getCachedMember(string $uuid): ?StoreMember
    {
        return $this->cachedMembers[$uuid] ?? null;
    }

    /**
     * Clear member cache
     */
    public function clearCache(): void
    {
        $this->cachedMembers = [];
    }

    /**
     * Clear specific member from cache
     */
    public function clearMemberCache(string $uuid): void
    {
        unset($this->cachedMembers[$uuid]);
    }
}