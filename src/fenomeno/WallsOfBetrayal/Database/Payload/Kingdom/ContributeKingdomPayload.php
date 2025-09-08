<?php

namespace fenomeno\WallsOfBetrayal\Database\Payload\Kingdom;

use fenomeno\WallsOfBetrayal\Commands\Arguments\KingdomDataFilterArgument;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use InvalidArgumentException;

final readonly class ContributeKingdomPayload extends IdPayload
{

    public function __construct(
        int|string    $id,
        public string $type,
        public int    $amount
    )
    {
        if (!in_array(strtolower($type), array_map('strtolower', KingdomDataFilterArgument::$VALUES))) {
            throw new InvalidArgumentException("Kingdom data must be : " . implode(', ', KingdomDataFilterArgument::$VALUES));
        }
        parent::__construct($id);
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'amount' => $this->amount
        ]);
    }

}