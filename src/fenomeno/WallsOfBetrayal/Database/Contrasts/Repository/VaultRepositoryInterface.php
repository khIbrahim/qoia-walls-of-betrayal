<?php

namespace fenomeno\WallsOfBetrayal\Database\Contrasts\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\CloseVaultPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Vault\VaultOpenPayload;
use Generator;

interface VaultRepositoryInterface extends RepositoryInterface
{

    public function open(VaultOpenPayload $payload): Generator;

    public function close(CloseVaultPayload $payload, array $items = []): Generator;

    public function read(string $data) : array;

    public function write(array $c) : string;

}