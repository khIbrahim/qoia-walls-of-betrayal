<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\Main;
use pocketmine\lang\Translatable;
use pocketmine\player\chat\ChatFormatter;
use pocketmine\player\Player;

class WobChatFormatter implements ChatFormatter
{

    public function __construct(private readonly string $message)
    {
    }

    public function format(string $username, string $message): Translatable|string
    {
        return $this->message;
    }
}