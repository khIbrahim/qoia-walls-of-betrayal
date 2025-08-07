<?php
namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\Sound;

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

    public function broadcastMessage(string $message, array $extraTags = [], ?string $default = null): void
    {
        foreach ($this->getOnlineMembers() as $member){
            MessagesUtils::sendTo($member, $message, $extraTags, $default);
        }
    }

    public function broadcastSound(Sound $sound): void
    {
        foreach ($this->getOnlineMembers() as $player){
            $player->broadcastSound($sound);
        }
    }

    /** @return Player[] */
    public function getOnlineMembers(): array
    {
        $members = [];

        foreach (Server::getInstance()->getOnlinePlayers() as $player){
            $session = Session::get($player);
            if ($session->isLoaded() && $session->getKingdom() !== null && $session->getKingdom()->id === $this->id){
                $members[] = $player;
            }
        }

        return $members;
    }

}