<?php

namespace fenomeno\WallsOfBetrayal\Handlers;

use fenomeno\WallsOfBetrayal\Events\PlayerJoinWobEvent;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\player\Player;
use pocketmine\world\sound\PopSound;

class PlayerJoinHandler
{

    public static function handle(Player $player): void
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            $player->kick(MessagesUtils::getMessage('common.unstable'));
            return;
        }

        $ev = new PlayerJoinWobEvent($player);
        $ev->call();

        if($session->getKingdom() !== null){
            $kingdom = $session->getKingdom();
            $player->sendTitle("§c§lWALLS §6§lof §e§lBETRAYAL"); // j'enlève après
            MessagesUtils::sendTo($player, 'kingdoms.onJoin.' . $kingdom->id);
            $kingdom->broadcastMessage('kingdoms.onJoin.broadcast', [
                '{PLAYER}'  => $player->getName(),
                '{KINGDOM}' => $kingdom->displayName
            ]);
            $kingdom->broadcastSound(new PopSound());
        }
    }

}