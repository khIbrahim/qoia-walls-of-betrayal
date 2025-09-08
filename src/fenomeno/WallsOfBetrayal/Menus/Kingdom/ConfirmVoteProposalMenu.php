<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Menus\Kingdom;

use fenomeno\WallsOfBetrayal\Config\KingdomVotingConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\CreateKingdomVotePayload;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\Vote\TargetAlreadyVotedException;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\ModalForm;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\DurationParser;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;
use Throwable;

final class ConfirmVoteProposalMenu
{

    public static function sendTo(
        Player          $player,
        string          $kingdomId,
        KingdomVoteType $voteType,
        string          $targetName,
        ?int            $sanctionDuration,
        string          $reason
    ): void
    {
        $readableDuration = DurationParser::getReadableDuration($sanctionDuration);

        $titleKey = match ($voteType) {
            KingdomVoteType::Kick => MessagesIds::KINGDOMS_KICK_CONFIRM_HEADER,
            KingdomVoteType::Ban => MessagesIds::KINGDOMS_BAN_CONFIRM_HEADER,
            default => MessagesIds::KINGDOMS_VOTE_CONFIRM_HEADER,
        };

        $title = MessagesUtils::getMessage($titleKey);

        $contentKey = match ($voteType) {
            KingdomVoteType::Kick => MessagesIds::KINGDOMS_KICK_CONFIRM_CONTENT,
            KingdomVoteType::Ban => MessagesIds::KINGDOMS_BAN_CONFIRM_CONTENT,
            default => MessagesIds::KINGDOMS_VOTE_CONFIRM_CONTENT,
        };

        $content = MessagesUtils::getMessage($contentKey, [
            ExtraTags::PLAYER => $targetName,
            ExtraTags::KINGDOM => $kingdomId,
            ExtraTags::REASON => $reason,
            ExtraTags::DURATION => $readableDuration,
            ExtraTags::TYPE => $voteType->value,
        ]);

        $form = new ModalForm(
            title: $title,
            text: $content,
            onSubmit: function (Player $player, bool $choice) use ($readableDuration, $voteType, $kingdomId, $targetName, $reason, $sanctionDuration): void {
                if (!$choice) {
                    MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_CANCELLED, [
                        ExtraTags::TYPE => $voteType->value
                    ]);
                    return;
                }

                Await::f2c(function () use ($readableDuration, $player, $voteType, $kingdomId, $targetName, $reason, $sanctionDuration) {
                    try {
                        $votingConfig = new KingdomVotingConfig(Main::getInstance());
                        $voteDuration = $votingConfig->getVoteDurationSeconds();
                        $voteExpiresAt = time() + $voteDuration;

                        yield from Main::getInstance()->getKingdomVoteManager()->create(
                            new CreateKingdomVotePayload(
                                $kingdomId,
                                $voteType->value,
                                $targetName,
                                $player->getName(),
                                $reason,
                                $sanctionDuration !== null ? $sanctionDuration - time() : null,
                                $voteExpiresAt,
                            )
                        );

                        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_CREATED, [
                            ExtraTags::TYPE => $voteType->value,
                            ExtraTags::TARGET => $targetName,
                            ExtraTags::REASON => $reason,
                            ExtraTags::DURATION => $readableDuration
                        ]);

                    } catch (TargetAlreadyVotedException) {
                        MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_VOTE_ALREADY_EXISTS, [
                            ExtraTags::TYPE => $voteType->value,
                            ExtraTags::TARGET => $targetName
                        ]);
                    } catch (Throwable $e) {
                        Utils::onFailure(
                            $e,
                            $player,
                            "Failed to create $voteType->value vote for $targetName by {$player->getName()} for $reason (duration {$sanctionDuration}s)"
                        );
                    }
                });
            },
            yesButtonText: MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_CONFIRM_YES),
            noButtonText: MessagesUtils::getMessage(MessagesIds::KINGDOMS_VOTE_CONFIRM_NO)
        );

        $player->sendForm($form);
    }
}
