<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Types;

interface PageableInventory
{

    public function getPage(): int;
    public function setPage(int $page): void;
    public function nextPage(int $by = 1): void;
    public function previousPage(int $by = 1): void;

    public function getBatch(): int;
    public function setBatch(int $batch): void;

    public function getTotalPages(int $totalItems): int;

    public function clampPage(int $totalItems): void;

}