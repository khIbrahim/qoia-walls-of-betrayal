<?php

namespace fenomeno\WallsOfBetrayal\Menus\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteStatus;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use Generator;
use pocketmine\player\Player;

final class VoteListMenu
{
    /**
     * Affiche la liste des votes du royaume avec filtres type/statut.
     * @param Player $player
     * @param string $kingdomId
     * @param KingdomVoteType $type
     * @param KingdomVoteStatus|null $status
     */
    public static function send(Player $player, string $kingdomId, KingdomVoteType $type, ?KingdomVoteStatus $status = null): void
    {
        $manager = Main::getInstance()->getKingdomVoteManager();

        $votes = $manager->filter($kingdomId, $type, $status);
        if (count($votes) === 0) {
            $player->sendMessage(MessagesUtils::getMessage(MessagesIds::KINGDOMS_NO_VOTES, [ExtraTags::FILTER => ucfirst($status?->value ?? 'Active')]));
            return;
        }

        $options = [];
        $mapping = [];
        foreach ($votes as $v) {
            $options[] = new MenuOption(MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_LIST_MENU_ROW, [
                ExtraTags::ID => (string)$v->id,
                ExtraTags::TYPE => strtoupper($v->type->value),
                ExtraTags::STATUS => strtoupper($v->status->value),
                ExtraTags::TARGET => $v->target,
                ExtraTags::FOR => (string)$v->votesFor,
                ExtraTags::AGAINST => (string)$v->votesAgainst,
                ExtraTags::EXPIRES_AT => date('Y-m-d H:i', $v->expiresAt),
                ExtraTags::PROPOSED_BY => $v->proposedBy,
                ExtraTags::REASON => $v->reason,
                ExtraTags::CREATED_AT => date('Y-m-d H:i', $v->createdAt),
            ]));
            $mapping[] = $v->id;
        }

        $menu = new MenuForm(
            title: MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_LIST_MENU_HEADER),
            text: MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_LIST_MENU_TEXT, [
                ExtraTags::KINGDOM => $kingdomId,
                ExtraTags::FILTERS => trim("type=$type->value " . ($status ? "status=$status->value " : "")) ?: '—',
            ]),
            options: $options,
            onSubmit: function (Player $player, int $selectedIndex) use ($mapping): void {
                $voteId = $mapping[$selectedIndex] ?? null;
                if ($voteId === null) return;

                $manager = Main::getInstance()->getKingdomVoteManager();
                Await::f2c(function () use ($player, $voteId, $manager): Generator {
                    /** @var KingdomVote $vote */
                    $vote = yield from $manager->refreshVote($voteId);

                    if ($vote === null) {
                        $player->sendMessage("§cUnknown vote");
                        return;
                    }

                    /** @var bool|null $choice */
                    $choice = yield from $manager->getVoterChoice($voteId, $player->getName());

                    if ($vote->type == KingdomVoteType::Kick) {
                        VoteMenu::sendVoteDetailsMenu(
                            $player,
                            $vote,
                            $choice
                        );

                        return;
                    }

                    $player->sendMessage("le reste arrive");
                });
            }
        );

        $player->sendForm($menu);
    }
}
