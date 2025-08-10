<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\Sessions\Session;
use pocketmine\lang\Translatable;
use pocketmine\player\chat\ChatFormatter;
use pocketmine\utils\TextFormat;

class WobChatFormatter implements ChatFormatter
{

    public function __construct(private readonly Session $session)
    {
    }

    public function format(string $username, string $message): Translatable|string
    {
        $rank      = "Wanderer"; // todo THE RANKS au passage suggère moi un rank de base pour wob je suis pas inspiré ni rp ni ux ui ni design
        $rankColor = TextFormat::GRAY;
        if (! $this->session->isLoaded() || $this->session->getKingdom() === null) {
            return MessagesUtils::getMessage('events.chat.noLoaded', [
                '{USERNAME}' => $username,
                '{MESSAGE}' => $message,
            ]);
        }

        return MessagesUtils::getMessage('events.chat.loaded', [
            '{USERNAME}'   => $username,
            '{MESSAGE}'    => $message,
            '{KINGDOM}'    => $this->session->getKingdom()->displayName,
            '{RANK}'       => $rank,
            '{RANK_COLOR}' => $rankColor
        ]);
    }
}