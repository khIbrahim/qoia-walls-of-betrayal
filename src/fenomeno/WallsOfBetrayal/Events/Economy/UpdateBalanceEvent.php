<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy;

abstract class UpdateBalanceEvent extends EconomyEvent
{

    public function __construct(
        private readonly string  $username,
        private readonly ?string $uuid,
        private int              $amount
    ){}

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}