<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\IncrementKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\InsertKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\LoadKitRequirementPayload;
use pocketmine\promise\Promise;

interface KitRequirementRepositoryInterface
{

    public function load(LoadKitRequirementPayload $payload): Promise;

    public function insert(InsertKitRequirementPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void;

    public function increment(IncrementKitRequirementPayload $payload, \Closure $onSuccess, \Closure $onFailure): void;

}