<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Menus\Kingdom;

use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomVoteAlreadySentException;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;
use Throwable;

final class VoteMenu
{

    public static function sendVoteDetailsMenu(Player $player, KingdomVote $vote, ?bool $currentVoterChoice = null): void
    {
        [$voteId, $votesFor, $votesAgainst, $createdAtUnix, $expiresAtUnix, $target, $reason, $proposedBy] =
        [$vote->id, $vote->votesFor, $vote->votesAgainst, $vote->createdAt ?? time(), $vote->expiresAt, $vote->target, $vote->reason, $vote->proposedBy];

        $title = MessagesUtils::getMessage('Kingdom Vote — ' . $vote->type->getDisplayName());

        $bar      = self::buildBar($votesFor, $votesAgainst);
        $leader   = self::computeLeader($votesFor, $votesAgainst);
        $created  = date('Y-m-d H:i', $createdAtUnix);
        $sanction = DurationParser::getReadableDuration($vote->sanctionDuration, false);
        $timeLeft = DurationParser::getReadableDuration($expiresAtUnix);

        $total = $votesFor + $votesAgainst;

        $leaderTxt = match ($leader) {
            'for'     => MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_LEADING_FOR, [], '§6FOR is leading'),
            'against' => MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_LEADING_AGAINST, [], '§6AGAINST is leading'),
            default   => MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_LEADING_TIED, [], '§7Tied'),
        };

        if ($currentVoterChoice) {
            $youVotedTxt = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_YOU_VOTED_FOR, [], '§7Your vote: §aFOR');
        } elseif ($currentVoterChoice === null) {
            $youVotedTxt = '-';
        } else {
            $youVotedTxt = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_YOU_VOTED_AGAINST, [], '§7Your vote: §cAGAINST');
        }

        $lines = [
            "§4⚔ §fRoyal Vote — §7" . $vote->type->getDisplayName(),
            "",
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_TARGET, [ExtraTags::PLAYER => $target], '§7Target: §6{PLAYER}'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_REASON, [ExtraTags::REASON => $reason], '§7Reason: §f{REASON}'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_PROPOSED_BY, [ExtraTags::PLAYER => $proposedBy], '§7Proposed by: §f{PLAYER}'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_CREATED, [ExtraTags::DATE => $created], '§7Created: §f{DATE}'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_EXPIRES, [ExtraTags::DATE => $sanction], '§7Expires: §f{DATE}'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_FIELD_TIMELEFT, [ExtraTags::TIME => $timeLeft], '§7Time left: §f{TIME}'),
            "",
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_COUNTS, ['{FOR}' => (string)$votesFor, '{AGAINST}' => (string)$votesAgainst, '{TOTAL}' => (string)$total], '§7Votes — §aFOR: §f{FOR} §7| §cAGAINST: §f{AGAINST} §7(§f{TOTAL}§7)'),
            MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_PROGRESS, ['{BAR}' => $bar], '§7Progress: §f{BAR}'),
            $leaderTxt,
        ];
        if ($youVotedTxt !== '') {
            $lines[] = $youVotedTxt;
        }
        $text = implode("\n", $lines);

        if ($currentVoterChoice) {
            $optFor = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_FOR_KEEP, [], 'Keep §aFOR');
            $optAgainst = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_AGAINST_SWITCH, [], 'Switch to §cAGAINST');
        } elseif ($currentVoterChoice === null) {
            $optFor = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_FOR, [], 'Vote §aFOR');
            $optAgainst = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_AGAINST, [], 'Vote §cAGAINST');
        } else {
            $optAgainst = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_AGAINST_KEEP, [], 'Keep §cAGAINST');
            $optFor = MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_FOR_SWITCH, [], 'Switch to §aFOR');
        }

        $menu = new MenuForm(
            title: $title,
            text: $text,
            options: [
                new MenuOption($optFor),
                new MenuOption($optAgainst),
                new MenuOption(MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_MENU_ACTION_VIEW_STATS, [], 'View statistics'))
            ],
            onSubmit: function (Player $player, int $selectedOption) use ($currentVoterChoice, $voteId): void {
                if ($selectedOption === 2) {
                    VoteQuorumMenu::send($player, $voteId);
                    return;
                }

                $voteFor = ($selectedOption === 0);

                if ($currentVoterChoice !== null && $currentVoterChoice === $voteFor) {
                    MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_ALREADY_VOTED_SAME, [ExtraTags::VOTE_CHOICE => $voteFor ? 'FOR' : 'AGAINST']);
                    return;
                }

                Await::f2c(function () use ($voteFor, $player, $voteId) {
                    try {
                        yield from Main::getInstance()->getKingdomVoteManager()->cast($voteId, $player->getUniqueId()->toString(), $player->getName(), $voteFor);

                        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_SUCCESS);
                    } catch (KingdomVoteAlreadySentException) {
                        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_ALREADY_VOTED, [ExtraTags::VOTE_CHOICE => $voteFor ? 'FOR' : 'AGAINST']);
                    } catch (Throwable $e) {
                        Utils::onFailure($e, $player, "Failed to vote by {$player->getName()} for vote id $voteId: {$e->getMessage()}");
                    }
                });
            }
        );

        $player->sendForm($menu);
    }

    private static function buildBar(int $for, int $against): string
    {
        $total = max(1, $for + $against);
        $len = 10;
        $forLen = (int)round(($for / $total) * $len);
        $againstLen = $len - $forLen;

        return "§a" . str_repeat("▰", $forLen) . "§c" . str_repeat("▰", $againstLen) . "§7 ($for/$total)";
    }

    private static function computeLeader(int $for, int $against): string
    {
        if ($for > $against) return 'for';
        if ($against > $for) return 'against';
        return 'tie';
    }
}
