<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Manager\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomSanction;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Sanction\CreateKingdomSanctionPayload;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use Throwable;

final class KingdomSanctionManager
{
    /** @var array<string, array<string, KingdomSanction>> */
    private array $activeSanctions = [];

    private bool $loaded = false;

    public function __construct(private readonly Main $main)
    {
        $this->syncLoadActiveSanctions();
    }

    private function syncLoadActiveSanctions(): void
    {
        $this->main->getDatabaseManager()->getKingdomRepository()->loadActiveSanctions(
            function (array $sanctions): void {
                foreach ($sanctions as $sanction) {
                    $this->registerSanction($sanction);
                }
                $this->loaded = true;
                $this->main->getLogger()->info("§aKINGDOM SANCTIONS - Loaded (" . count($sanctions) . ")");
            },
            fn(Throwable $e) => Utils::onFailure($e, null, "Failed to load kingdom sanctions: " . $e->getMessage())
        );
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    private function registerSanction(KingdomSanction $sanction): void
    {
        if (!isset($this->activeSanctions[$sanction->kingdomId])) {
            $this->activeSanctions[$sanction->kingdomId] = [];
        }

        $this->activeSanctions[$sanction->kingdomId][$sanction->targetUuid] = $sanction;
    }

    private function unregisterSanction(string $kingdomId, string $targetUuid): void
    {
        if (isset($this->activeSanctions[$kingdomId][$targetUuid])) {
            unset($this->activeSanctions[$kingdomId][$targetUuid]);

            if (empty($this->activeSanctions[$kingdomId])) {
                unset($this->activeSanctions[$kingdomId]);
            }
        }
    }

    public function addSanction(string $targetUuid, string $targetName, string $kingdomId, ?int $expiresAt, string $reason, string $staff): Generator
    {
        try {
            $payload = new CreateKingdomSanctionPayload(
                $kingdomId,
                $targetUuid,
                $targetName,
                $reason,
                $staff,
                $expiresAt
            );

            /** @var int $sanctionId */
            $sanctionId = yield from $this->main->getDatabaseManager()->getKingdomRepository()->createSanction($payload);

            if ($sanctionId > 0) {
                $sanction = new KingdomSanction(
                    $sanctionId,
                    $kingdomId,
                    $targetUuid,
                    $targetName,
                    $reason,
                    $staff,
                    time(),
                    $expiresAt,
                    true
                );

                $this->registerSanction($sanction);

                return $sanctionId;
            }

            return false;
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to add kingdom sanction: " . $e->getMessage());
            return false;
        }
    }

    public function removeSanction(string $kingdomId, string $targetUuid): Generator
    {
        try {
            $success = yield from $this->main->getDatabaseManager()->getKingdomRepository()->deactivateSanction($kingdomId, $targetUuid);

            if ($success) {
                $this->unregisterSanction($kingdomId, $targetUuid);
                return true;
            }

            return false;
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to remove kingdom sanction: " . $e->getMessage());
            return false;
        }
    }

    public function isSanctioned(string $kingdomId, string $targetUuid): bool
    {
        if (isset($this->activeSanctions[$kingdomId][$targetUuid])) {
            $sanction = $this->activeSanctions[$kingdomId][$targetUuid];

            if ($sanction->expiresAt === null || $sanction->expiresAt > time()) {
                return true;
            }

            $this->unregisterSanction($kingdomId, $targetUuid);

            Await::g2c(
                $this->main->getDatabaseManager()->getKingdomRepository()->deactivateSanction($kingdomId, $targetUuid),
                function (bool $success): void {
                },
                fn(Throwable $e) => Utils::onFailure($e, null, "Failed to deactivate expired sanction: " . $e->getMessage())
            );

            return false;
        }

        return false;
    }

    public function getSanction(string $kingdomId, string $targetUuid): ?KingdomSanction
    {
        if ($this->isSanctioned($kingdomId, $targetUuid)) {
            return $this->activeSanctions[$kingdomId][$targetUuid] ?? null;
        }

        return null;
    }

    public function cleanupExpiredSanctions(): void
    {
        $now = time();
        $expiredCount = 0;

        foreach ($this->activeSanctions as $kingdomId => $playerSanctions) {
            foreach ($playerSanctions as $targetUuid => $sanction) {
                if ($sanction->expiresAt !== null && $sanction->expiresAt <= $now) {
                    $this->unregisterSanction($kingdomId, $targetUuid);
                    $expiredCount++;

                    Await::g2c(
                        $this->main->getDatabaseManager()->getKingdomRepository()->deactivateSanction($kingdomId, $targetUuid),
                        function (bool $success): void {
                        },
                        fn(Throwable $e) => Utils::onFailure($e, null, "Failed to deactivate expired sanction: " . $e->getMessage())
                    );
                }
            }
        }

        if ($expiredCount > 0) {
            $this->main->getLogger()->info("§aKINGDOM SANCTIONS - Cleaned up $expiredCount expired sanctions");
        }
    }
}
