<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomEvent;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\XpLevelUpSound;
use Throwable;

class JoinKingdomHandler
{

    public static function join(Player $player, Kingdom $kingdom): void
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            $player->kick(MessagesUtils::getMessage('unstable'));
            return;
        }

        if ($session->getKingdom() !== null){
            MessagesUtils::sendTo($player, 'alreadyInKingdom');
            return;
        }

        $session->setChoosingKingdom(true);
        $payload = new SetPlayerKingdomPayload($player->getUniqueId()->toString(), $kingdom->id, $kingdom->abilities);
        $ev      = new PlayerJoinKingdomEvent($player, $kingdom);
        Main::getInstance()->getDatabaseManager()->getPlayerRepository()->updatePlayerKingdom($payload, function () use ($ev, $player, $kingdom, $session) {
            $ev->call();
            $session->setKingdom($kingdom);
            $session->addAbilities($kingdom->abilities);
            $session->setChoosingKingdom(false);
            if($kingdom->spawn !== null){
                $player->teleport($kingdom->spawn);
            }
            MessagesUtils::sendTo($player, 'kingdoms.onJoin.' . $kingdom->id);
            $player->broadcastSound(new XpLevelUpSound(1));
            $player->getWorld()->addParticle($player->getPosition(), new EndermanTeleportParticle());
            $kingdom->broadcastMessage('kingdoms.onFirstJoin.' . $kingdom->id, ['{PLAYER}' => $player->getName()]);
            //TODO HISTORIQUE
        }, function(Throwable $e) use ($ev, $session, $player) {
            $ev->cancel();
            $session->setChoosingKingdom(false);
            $player->sendMessage(TextFormat::RED . "An error occurred while choosing the kingdom :" . $e->getMessage());
        });
    }

}