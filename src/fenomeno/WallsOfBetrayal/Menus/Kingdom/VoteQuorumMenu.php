<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Menus\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\DTO\VoteStatisticsDTO;
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

final class VoteQuorumMenu
{
    public static function send(Player $player, int $voteId): void
    {
        $main = Main::getInstance();
        $manager = $main->getKingdomVoteManager();
        $vote = $manager->getById($voteId);

        if ($vote === null) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_NOT_FOUND, [ExtraTags::ID => $voteId]);
            return;
        }

        $statistics = $manager->getVoteQuorumStatistics($vote->id);

        if ($statistics === null) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_STATISTICS_UNAVAILABLE);
            return;
        }

        $title = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_MENU_TITLE);
        $text = self::buildStatisticsText($statistics, $vote);
        
        $menu = new MenuForm(
            title: $title,
            text: $text,
            options: [
                new MenuOption(MessagesUtils::getMessage(MessagesIds::GENERAL_BACK))
            ],
            onSubmit: function (Player $player, int $selectedOption) use ($manager, $vote): void {
                if ($selectedOption === 0) {
                    Await::f2c(function () use ($player, $vote, $manager): Generator {
                        /** @var KingdomVote $vote */
                        $vote = yield from $manager->refreshVote($vote->id);

                        if ($vote === null) {
                            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_NOT_FOUND, [ExtraTags::ID => $vote->id]);
                            return;
                        }

                        /** @var bool|null $choice */
                        $choice = yield from $manager->getVoterChoice($vote->id, $player->getName());

                        if ($vote->type == KingdomVoteType::Kick) {
                            VoteMenu::sendVoteDetailsMenu($player, $vote, $choice);
                        }
                    });
                }
            }
        );
        
        $player->sendForm($menu);
    }

    private static function buildStatisticsText(VoteStatisticsDTO $stats, KingdomVote $vote): string
    {
        $lines = [];
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_GENERAL_HEADER);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_GENERAL_TYPE, [
            ExtraTags::TYPE => strtoupper($vote->type->value)
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_GENERAL_TARGET, [
            ExtraTags::TARGET => $vote->target
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_GENERAL_REASON, [
            ExtraTags::REASON => $vote->reason
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_GENERAL_PROPOSED_BY, [
            ExtraTags::PROPOSED_BY => $vote->proposedBy
        ]);
        $lines[] = "";
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_PARTICIPATION_HEADER);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_PARTICIPATION_TOTAL, [
            ExtraTags::TOTAL => (string) $stats->totalMembers
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_PARTICIPATION_VOTES, [
            ExtraTags::VOTES => (string) $stats->totalVotes
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_PARTICIPATION_RATE, [
            ExtraTags::PARTICIPATION_PERCENT => number_format($stats->participationPercent, 1)
        ]);
        $lines[] = "";
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_RESULTS_HEADER);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_RESULTS_FOR, [
            ExtraTags::FOR => (string) $stats->votesFor,
            ExtraTags::PERCENT => number_format($stats->forPercent, 1)
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_RESULTS_AGAINST, [
            ExtraTags::AGAINST => (string) $stats->votesAgainst,
            ExtraTags::PERCENT => number_format(100 - $stats->forPercent, 1)
        ]);
        $lines[] = "";
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_REQUIREMENTS_HEADER);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_PARTICIPATION, [
            ExtraTags::QUORUM_PERCENT => number_format($stats->quorumPercent, 1)
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_MAJORITY, [
            ExtraTags::MAJORITY_PERCENT => number_format($stats->majorityRequired, 1)
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_REQUIREMENTS_MIN_VOTES, [
            ExtraTags::MINIMUM_VOTES => (string) $stats->minVotesRequired
        ]);
        $lines[] = "";
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_CONDITIONS_HEADER);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_CONDITIONS_QUORUM_MET, [
            ExtraTags::STATUS => $stats->isQuorumMet ? "§a✓" : "§c✗"
        ]);
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_CONDITIONS_MAJORITY_MET, [
            ExtraTags::STATUS => $stats->isMajorityMet ? "§a✓" : "§c✗"
        ]);
        $lines[] = "";
        
        $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_FINAL_HEADER);
        if ($stats->isQuorumMet && $stats->isMajorityMet) {
            $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_FINAL_SUCCESS);
        } else {
            $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_FINAL_FAILURE);
            if (! $stats->isQuorumMet) {
                $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_FINAL_REASON_QUORUM);
            }
            if (! $stats->isMajorityMet) {
                $lines[] = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_QUORUM_FINAL_REASON_MAJORITY);
            }
        }
        
        return implode("\n", $lines);
    }
}
