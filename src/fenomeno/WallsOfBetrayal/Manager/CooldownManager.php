<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\GetActiveCooldownsPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\RemoveCooldownPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Cooldown\UpsertCooldownPayload;
use fenomeno\WallsOfBetrayal\DTO\CooldownEntry;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\scheduler\ClosureTask;

final class CooldownManager
{

    /** @var array<string, array<string, int>> */
    private array $cached = [];
    /** @var array<string, array<string, int>> */
    private array $persisted = [];

    public const PERSISTENCE_THRESHOLD = 60 * 5;

    public function __construct(private readonly Main $main){
        $this->asyncLoadPersistedCooldowns();
        $interval = (int) $this->main->getConfig()->getNested('cooldowns.cleanup_interval_seconds', self::PERSISTENCE_THRESHOLD);
        $this->main->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(fn() => $this->cleanupExpired()),
            max(20, 20 * $interval)
        );
    }

    public function isOnCooldown(string $t, string $id): bool {
        $now = time();
        $exp = $this->cached[$t][$id] ?? $this->persisted[$t][$id] ?? null;
        if ($exp === null) return false;

        if ($exp <= $now) {
            $this->removeCooldown($t, $id);
            return false;
        }
        return true;
    }

    public function setCooldown(string $type, string $identifier, int $duration): void {
        $expiryTime = time() + $duration;

        if ($duration >= self::PERSISTENCE_THRESHOLD) {
            $this->persisted[$type] ??= [];
            $this->persisted[$type][$identifier] = $expiryTime;
            $this->saveToDatabase($type, $identifier, $expiryTime);
        } else {
            $this->cached[$type] ??= [];
            $this->cached[$type][$identifier] = $expiryTime;
        }
    }

    public function removeCooldown(string $type, string $identifier): void
    {
        unset($this->cached[$type][$identifier]);
        if (isset($this->persisted[$type][$identifier])) {
            unset($this->persisted[$type][$identifier]);
            $this->removeFromDatabase($type, $identifier);
        }
    }

    public function getCooldownRemaining(string $type, string $identifier): int
    {
        if ($this->isOnCooldown($type, $identifier)) {
            if (isset($this->cached[$type][$identifier])){
                return $this->cached[$type][$identifier] - time();
            } else {
                return $this->persisted[$type][$identifier] - time();
            }
        }

        return 0;
    }

    private function asyncLoadPersistedCooldowns(): void {
        Await::g2c(
            $this->main->getDatabaseManager()->getCooldownRepository()->getAll(new GetActiveCooldownsPayload()),
            function (array $entries): void {
                $now = time();
                /** @var CooldownEntry $e */
                foreach ($entries as $e) {
                    $remain = $e->expiryTime - $now;
                    if ($remain < self::PERSISTENCE_THRESHOLD) {
                        $this->cached[$e->type] ??= [];
                        $this->cached[$e->type][$e->identifier] = $e->expiryTime;
                    } else {
                        $this->persisted[$e->type] ??= [];
                        $this->persisted[$e->type][$e->identifier] = $e->expiryTime;
                    }
                }
                $counts = $this->getCooldownCount();
                $this->main->getLogger()->info("COOLDOWNS - §aLoaded {$counts['persistent']} persisted, {$counts['memory']} cached");
            },
            function (\Throwable $er): void {
                $this->main->getLogger()->error("Failed to load cooldowns: " . $er->getMessage());
                $this->main->getLogger()->logException($er);
            }
        );
    }

    private function saveToDatabase(string $type, string $identifier, int $expiryTime): void
    {
        $duration = $expiryTime - time();
        Await::g2c(
            $this->main->getDatabaseManager()->getCooldownRepository()->upsert(new UpsertCooldownPayload($identifier, $type, $expiryTime)),
            function () use ($duration, $expiryTime, $identifier, $type) {
                $this->persisted[$type][$identifier] = $expiryTime;
                $this->main->getLogger()->info("§7COOLDOWNS - Cooldown $type - $identifier for $duration seconds was set successfully.");
            },
            function (\Throwable $er) use ($duration, $expiryTime, $identifier, $type): void {
                $this->main->getLogger()->error("Failed to set cooldown type: $type to $identifier for $duration: " . $er->getMessage());
            }
        );
    }

    private function removeFromDatabase(string $type, string $identifier): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getCooldownRepository()->remove(new RemoveCooldownPayload($type, $identifier)),
            function () use ($identifier, $type) {
                $this->main->getLogger()->info("§7COOLDOWNS - Removed $type - $identifier successfully.");
            },
            function (\Throwable $er) use ($identifier, $type): void {
                $this->main->getLogger()->error("Failed to remove cooldown type: $type for $identifier : " . $er->getMessage());
            }
        );
    }

    private function cleanupExpired(): void {
        $now = time();
        $cleaned = 0;

        foreach ($this->cached as $type => $cooldowns) {
            foreach ($cooldowns as $id => $exp) {
                if ($exp <= $now) {
                    // cached only → pas de DB
                    unset($this->cached[$type][$id]);
                    $cleaned++;
                }
            }
            if (empty($this->cached[$type])) unset($this->cached[$type]);
        }

        foreach ($this->persisted as $type => $cooldowns) {
            foreach ($cooldowns as $id => $exp) {
                if ($exp <= $now) {
                    // persisted → DB delete
                    unset($this->persisted[$type][$id]);
                    $this->removeFromDatabase($type, $id);
                    $cleaned++;
                }
            }
            if (empty($this->persisted[$type])) unset($this->persisted[$type]);
        }

        if ($cleaned > 0) {
            $this->main->getLogger()->debug("COOLDOWNS - Cleaned $cleaned expired cooldowns.");
        }
    }

    /** @return array{memory:int,persistent:int} */
    public function getCooldownCount(): array {
        return [
            'memory' => array_sum(array_map('count', $this->cached ?: [])),
            'persistent' => array_sum(array_map('count', $this->persisted ?: [])),
        ];
    }

}