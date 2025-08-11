<?php

namespace fenomeno\WallsOfBetrayal\Inventory\Traits;

trait InventoryPaginatorTrait
{

    private int $page = 0;
    protected int $batch = 10;

    public function getPage(): int
    {
        return $this->page;
    }
    public function setPage(int $page): void
    {
        $this->page = max(0, $page);
    }
    public function nextPage(int $by = 1): void
    {
        $this->page += max(1, $by);
    }
    public function previousPage(int $by = 1): void
    {
        $this->page = max(0, $this->page - max(1, $by));
    }

    public function getBatch(): int {
        return $this->batch;
    }
    public function setBatch(int $batch): void {
        $this->batch = max(1, $batch);
    }

    public function getTotalPages(int $totalItems): int
    {
        return (int) max(1, ceil($totalItems / max(1, $this->batch)));
    }

    public function clampPage(int $totalItems): void {
        $totalPages = $this->getTotalPages($totalItems);
        if ($this->page >= $totalPages) $this->page = $totalPages - 1;
    }

}