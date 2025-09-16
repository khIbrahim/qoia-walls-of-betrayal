<?php

namespace fenomeno\WallsOfBetrayal\Logs\Domain;

use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Logs\LoggingManager;
use fenomeno\WallsOfBetrayal\Logs\LogLevel;

class KillLogEvent implements DomainEventInterface
{
    public function __construct(
        private readonly string $killer,
        private readonly string $victim,
        private readonly string $weapon,
        /** @var string[] */
        private readonly array  $opponents,
        private readonly int    $killerHealth,
        private readonly bool   $teamKill,
        private readonly bool   $inKingdom,
        private readonly int    $createdAt
    ){}

    public static function create(
        string $killer,
        string $victim,
        string $weapon = 'unknown',
        array  $opponents     = [],
        int    $killer_health = -1,
        bool   $teamkill      = false,
        bool   $in_kingdom    = false,
        ?int   $created_at    = null
    ): self
    {
        return new self(
            $killer,
            $victim,
            $weapon,
            $opponents,
            $killer_health,
            $teamkill,
            $in_kingdom,
            $created_at ?? time()
        );
    }

    public function getChannel(): string
    {
        return 'kills';
    }

    public function getLevel(): int
    {
        return LogLevel::INFO;
    }

    public function getMessage(): string
    {
        return '{KILLER} has killed {VICTIM} with {WEAPON}';
    }

    public function getContext(): array
    {
        return [
            'killer'        => $this->killer,
            'victim'        => $this->victim,
            'weapon'        => $this->weapon,
            'opponents'     => $this->opponents,
            'killer_health' => $this->killerHealth,
            'teamkill'      => $this->teamKill,
            'in_kingdom'    => $this->inKingdom,
            'created_at'    => $this->createdAt
        ];
    }

    public function getExtra(): array
    {
        return [];
    }

    public function onRecord(LoggingManager $manager): void
    {
        Await::g2c($manager->getMain()->getDatabaseManager()->getKillsRepository()->logKill($this));
    }

    public function jsonSerialize(): array
    {
        return [
            'killer'        => $this->killer,
            'victim'        => $this->victim,
            'weapon'        => $this->weapon,
            'opponents'     => json_encode($this->opponents),
            'killer_health' => $this->killerHealth,
            'teamkill'      => (int) $this->teamKill,
            'in_kingdom'    => (int) $this->inKingdom,
            'created_at'    => $this->createdAt
        ];
    }
}