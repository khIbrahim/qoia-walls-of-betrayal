<?php

namespace fenomeno\WallsOfBetrayal\Entities;

class EntityInfo {

    private string $class, $networkId, $name;
    private ?int $legacyId;

    public function __construct(string $class, string $networkId, string $name, ?int $legacyId){
        $this->class = $class;
        $this->networkId = $networkId;
        $this->name = $name;
        $this->legacyId = $legacyId;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getNetworkId(): string
    {
        return $this->networkId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|int
     */
    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

}