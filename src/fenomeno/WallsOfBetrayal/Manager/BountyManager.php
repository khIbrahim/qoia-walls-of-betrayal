<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomBounty;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\CreateKingdomBountyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\DeactivateBountyPayload;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomBountyAlreadyExists;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use Throwable;

final class BountyManager
{

    /** @var KingdomBounty[] */
    private array $bounties = [];

    private bool $loaded = false;

    public function __construct(private readonly Main $main)
    {
        $this->syncLoadAll();
    }

    private function syncLoadAll(): void
    {
        $this->main->getDatabaseManager()->getBountyRepository()
            ->loadActives(
                function (array $bounties) {
                    $this->bounties = $bounties;

                    $this->main->getLogger()->info("KINGDOM BOUNTIES - Loaded (" . count($bounties) . ") Bounties");
                    $this->loaded = true;
                },
                function (Throwable $e) {
                    Utils::onFailure($e, null, "Failed to load bounties: " . $e->getMessage());
                }
            );
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @throws KingdomBountyAlreadyExists
     */
    public function create(string $target, int $amount, string $kingdomId, string $placedBy, bool $strict): Generator
    {
        if ($this->exists($target, $kingdomId)) {
            throw new KingdomBountyAlreadyExists("Kingdom $kingdomId has already placed a bounty for $target");
        }

        $payload = new CreateKingdomBountyPayload($kingdomId, $target, $amount, $placedBy, $strict);
        $id = yield from $this->main->getDatabaseManager()->getBountyRepository()->create($payload);

        $this->bounties[$id] = $bounty = new KingdomBounty($id, $kingdomId, $target, $amount, $placedBy, time(), true);

        return $bounty;
    }

    public function exists(string $target, string $kingdomId): bool
    {
        foreach ($this->bounties as $bounty) {
            if ($bounty->getTargetPlayer() === $target && $bounty->getKingdomId() === $kingdomId && $bounty->isActive()) {
                return true;
            }
        }

        return false;
    }

    public function getAll(): array
    {
        return $this->bounties;
    }

    public function getBountyByTarget(string $target): ?KingdomBounty
    {
        foreach ($this->bounties as $bounty) {
            if (strtolower($bounty->getTargetPlayer()) === strtolower($target)) {
                return $bounty;
            }
        }

        return null;
    }

    public function deactivate(KingdomBounty $bounty, string $takenBy): Generator
    {
        $bounty->setActive(false);
        $bounty->setTakenBy($takenBy);

        $this->bounties[$bounty->getId()] = $bounty;

        yield from $this->main->getDatabaseManager()
            ->getBountyRepository()
            ->deactivate(new DeactivateBountyPayload(
                $bounty->getId(),
                $takenBy
            ));
    }

}