<?php
namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use pocketmine\item\Item;
use pocketmine\world\Position;

class Kingdom
{

    public function __construct(
        public string $id,
        public string $displayName,
        public string $color,
        public string $description,
        public ?Item $item = null,
        public ?Position $spawn = null
    ){}

}