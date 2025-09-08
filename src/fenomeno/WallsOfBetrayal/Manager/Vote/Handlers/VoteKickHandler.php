<?php

namespace fenomeno\WallsOfBetrayal\Manager\Vote\Handlers;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\SetPlayerKingdomPayload;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use Throwable;

class VoteKickHandler implements VoteHandlerInterface
{
    public function handle(KingdomVote $vote): Generator
    {
        $main = Main::getInstance();
        $targetName = $vote->target;
        $kingdom = $main->getKingdomManager()->getKingdomById($vote->kingdomId);

        if ($kingdom === null) {
            return false;
        }

        $target = Server::getInstance()->getPlayerExact($targetName);
        if ($target instanceof Player) {
            $session = Session::get($target);
            if ($session->isLoaded() && $session->hasKingdom() && $session->getKingdom()->getId() === $vote->kingdomId) {
                try {
                    $session->setKingdom(null);
                    $main->getServerManager()->getLobbyManager()->teleport($target);

                    $expiryDate = $vote->sanctionDuration > 0 ? date('Y-m-d H:i:s', time() + $vote->sanctionDuration) : "Permanent";

                    MessagesUtils::sendTo($target, MessagesIds::KINGDOMS_VOTE_HANDLER_KICK_PASSED, [
                        ExtraTags::KINGDOM => $kingdom->getDisplayName(),
                        ExtraTags::TIME => $expiryDate,
                        ExtraTags::REASON => $vote->reason,
                        ExtraTags::PROPOSED_BY => $vote->proposedBy
                    ]);
                } catch (Throwable $e) {
                    Utils::onFailure($e, $target, "Failed to kick from kingdom.");
                }
            }
        }

        try {
            [$uuid, $username] = yield from $main->getDatabaseManager()->getPlayerRepository()->getUuidAndUsernameByName($targetName);

            yield from $main->getDatabaseManager()->getPlayerRepository()->updatePlayerKingdom(new SetPlayerKingdomPayload(
                uuid: $uuid,
                username: $username,
                kingdomId: null,
                abilities: []
            ));

            $expiresAt = $vote->sanctionDuration > 0 ? $vote->sanctionDuration + time() : null;

            return yield from $main->getKingdomManager()->addSanction(
                $kingdom,
                $targetName,
                $vote->reason,
                $expiresAt,
                $vote->proposedBy
            );
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to handle vote kick: " . $e->getMessage());
            return false;
        }
    }

    public function getType(): KingdomVoteType
    {
        return KingdomVoteType::Kick;
    }
}
