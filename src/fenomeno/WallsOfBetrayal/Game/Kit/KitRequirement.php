<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\IncrementKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Main;

class KitRequirement
{

    private int $dirtyProgress = 0;

    public function __construct(
        private readonly int                $id,
        private readonly string             $kingdomId,
        private readonly string             $kitId,
        private readonly KitRequirementType $type,
        private readonly mixed              $target,
        private readonly int                $amount,
        private int                         $progress = 0
    ){}

    public function getType(): KitRequirementType { return $this->type; }
    public function getTarget(): mixed { return $this->target; }
    public function getAmount(): int { return $this->amount; }
    public function getProgress(): int { return $this->progress; }
    public function getId(): int { return $this->id; }
    public function getKingdomId(): string { return $this->kingdomId; }
    public function getKitId(): string { return $this->kitId; }
    public function incrementProgress(?\Closure $onSuccess = null, ?\Closure $onFailure = null, bool $update = false): void
    {
        if ($update){
            Main::getInstance()->getDatabaseManager()->getKitRequirementRepository()->increment(
                new IncrementKitRequirementPayload(
                    id: $this->id,
                    kingdomId: $this->kingdomId,
                    kitId: $this->kitId,
                    progress: 1,
                    max: $this->amount
                ),
                function () use ($onSuccess) {
                    $this->progress++;
                    $onSuccess?->__invoke();
                },
                function (\Throwable $e) use ($onFailure) {
                    $onFailure?->__invoke($e);
                }
            );
        } else {
            try {
                $this->progress++;
                $this->dirtyProgress++;
                $onSuccess?->__invoke();
            } catch (\Throwable $e){
                $onFailure?->__invoke($e);
            }
        }
    }

    public function flush($onSuccess, $onFailure): void
    {
        if ($this->dirtyProgress <= 0){
            return;
        }

        Main::getInstance()->getDatabaseManager()->getKitRequirementRepository()->increment(
            new IncrementKitRequirementPayload(
                id: $this->id,
                kingdomId: $this->kingdomId,
                kitId: $this->kitId,
                progress: $this->dirtyProgress,
                max: $this->amount
            ),
            function () use ($onSuccess) {
                $this->dirtyProgress = 0;
                $onSuccess?->__invoke();
            },
            function (\Throwable $e) use ($onFailure) {
                $onFailure?->__invoke($e);
            }
        );
    }

    public function isComplete(): bool
    {
        return $this->progress >= $this->amount;
    }

    public function setProgress(int $requirementProgress): void
    {
        $this->progress = $requirementProgress;
    }

    public function consumeDirty(): int
    {
        $d = $this->dirtyProgress;
        if ($d > 0) {
            $this->dirtyProgress = 0;
        }
        return $d;
    }
}
