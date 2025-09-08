<?php

namespace fenomeno\WallsOfBetrayal\Game\Handlers;

use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinKingdomEvent;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
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
            $player->kick(MessagesUtils::getMessage('common.unstable'));
            return;
        }

        if ($session->getKingdom() !== null){
            MessagesUtils::sendTo($player, MessagesIds::ALREADY_IN_KINGDOM, [ExtraTags::KINGDOM => $session->getKingdom()->getDisplayName()]);
            return;
        }

        $session->setChoosingKingdom(true);

        $ev = new PlayerJoinKingdomEvent($player, $kingdom);
        $ev->call();
        if ($ev->isCancelled()) {
            return;
        }

        $kingdom = $ev->getKingdom();
        if ($kingdom->isExcluded($player->getUniqueId()->toString())) {
            $sanction = Main::getInstance()->getKingdomManager()->getSanctionManager()->getSanction($kingdom->id, $player->getUniqueId()->toString());
            if ($sanction === null) {
                MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_CANT_JOIN_EXCLUDED_NO_DETAILS, [ExtraTags::KINGDOM => $kingdom->getDisplayName()]);
                $session->setChoosingKingdom(false);
                return;
            }

            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_CANT_JOIN_EXCLUDED_WITH_DETAILS, [
                ExtraTags::KINGDOM => $kingdom->getDisplayName(),
                ExtraTags::DURATION => DurationParser::getReadableDuration($sanction->expiresAt),
                ExtraTags::PLAYER => $sanction->staff,
                ExtraTags::REASON => $sanction->reason
            ]);
            $session->setChoosingKingdom(false);
            return;
        }

        $payload = new SetPlayerKingdomPayload($player->getUniqueId()->toString(), strtolower($player->getName()), $kingdom->id, $kingdom->abilities);
        Await::f2c(function () use ($kingdom, $session, $ev, $player, $payload) {
            try {
                yield from Main::getInstance()->getDatabaseManager()->getPlayerRepository()->updatePlayerKingdom($payload);

                $session->setKingdom($kingdom);
                $session->addAbilities($kingdom->abilities);
                $session->setChoosingKingdom(false);
                if ($kingdom->getSpawn() !== null) {
                    $player->teleport($kingdom->getSpawn());
                }
                MessagesUtils::sendTo($player, 'kingdoms.onJoin.' . $kingdom->id);
                $player->broadcastSound(new XpLevelUpSound(1));
                $player->getWorld()->addParticle($player->getPosition(), new EndermanTeleportParticle());
                $kingdom->broadcastMessage('kingdoms.onFirstJoin.' . $kingdom->id, ['{PLAYER}' => $player->getName()]);
                //TODO HISTORIQUE
            } catch (Throwable $e) {
                $ev->cancel();
                $session->setChoosingKingdom(false);
                $player->sendMessage(TextFormat::RED . "An error occurred while choosing the kingdom :" . $e->getMessage());
            }
        });
    }

}