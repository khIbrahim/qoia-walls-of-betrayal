<?php

namespace fenomeno\WallsOfBetrayal\Events\Economy;

class TransferBalanceEvent extends EconomyEvent
{

    public function __construct(
        private readonly string  $senderUsername,
        private readonly string  $receiverUsername,
        private readonly ?string $senderUuid,
        private readonly ?string $receiverUuid,
        private int              $amount
    ){}

    /**
     * @return string
     */
    public function getSenderUsername(): string
    {
        return $this->senderUsername;
    }

    /**
     * @return string|null
     */
    public function getSenderUuid(): ?string
    {
        return $this->senderUuid;
    }

    /**
     * @return string
     */
    public function getReceiverUsername(): string
    {
        return $this->receiverUsername;
    }

    /**
     * @return string|null
     */
    public function getReceiverUuid(): ?string
    {
        return $this->receiverUuid;
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