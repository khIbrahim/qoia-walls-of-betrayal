<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy;

class InsertBalanceEvent extends EconomyEvent {
    public function __construct(private readonly string $username, private readonly string $uuid){}

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }
}