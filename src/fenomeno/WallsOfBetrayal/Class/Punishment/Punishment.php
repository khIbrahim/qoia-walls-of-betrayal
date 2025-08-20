<?php
namespace fenomeno\WallsOfBetrayal\Class\Punishment;

abstract class Punishment {

    private int $expiration;

    public function __construct(int $expiration)
    {
        $this->expiration = $expiration;
    }

    public function hasExpired() : bool {
        return $this->expiration - time() <= 0;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function getExpirationFormat() : string {
        return date('y/m/d à h-i-s', $this->expiration);
    }

}