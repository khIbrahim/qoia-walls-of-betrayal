<?php

declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Manager;

use Closure;
use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\CastKingdomVotePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\CreateKingdomVotePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\GetKingdomVoterChoicePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\UpdateKingdomVoteStatusPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\UpdateKingdomVoteVotes;
use fenomeno\WallsOfBetrayal\Database\Repository\KingdomVoteRepository;
use fenomeno\WallsOfBetrayal\DTO\VoteStatisticsDTO;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteStatus;
use fenomeno\WallsOfBetrayal\Enum\KingdomVoteType;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomVoteAlreadySentException;
use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\Vote\TargetAlreadyVotedException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Vote\Handlers\VoteHandlerInterface;
use fenomeno\WallsOfBetrayal\Manager\Vote\Handlers\VoteKickHandler;
use fenomeno\WallsOfBetrayal\Manager\Vote\VoteNotificationManager;
use fenomeno\WallsOfBetrayal\Manager\Vote\VoteQuorumManager;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\scheduler\ClosureTask;
use Throwable;

final class KingdomVoteManager
{
    /** @var array<int, KingdomVote> */
    private array $votes = [];

    private bool $loaded = false;

    /** @var array<string, VoteHandlerInterface> */
    private array $voteHandlers = [];
    
    private VoteNotificationManager $notificationManager;
    private VoteQuorumManager $quorumManager;

    public function __construct(private readonly Main $main)
    {
        $this->notificationManager = new VoteNotificationManager($main);
        $this->quorumManager       = new VoteQuorumManager($main);

        $this->initVoteHandlers();
    }

    private function repo(): KingdomVoteRepository
    {
        return $this->main->getDatabaseManager()->getKingdomVoteRepository();
    }

    private function syncLoadAll(): void
    {
        $this->repo()->load(
            function (array $votes): void {
                $this->votes  = $votes;
                $this->loaded = true;
                $this->main->getLogger()->info("§aKINGDOM VOTES - Loaded (" . count($votes) . ")");

                $this->main->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
                    foreach($this->votes as $vote){
                        Await::g2c(
                            $this->refreshVote($vote->id),
                            function () {},
                            fn(Throwable $e) => Utils::onFailure($e, null, "Failed to refresh vote #$vote->id: " . $e->getMessage())
                        );
                    }
                }), 20 * 60);
            },
            fn(Throwable $e) => Utils::onFailure($e, null, "Failed to load kingdom votes: " . $e->getMessage())
        );
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /** @return array<int, KingdomVote> */
    public function all(): array
    {
        return $this->votes;
    }

    public function getById(int $id): ?KingdomVote
    {
        return $this->votes[$id] ?? null;
    }

    /** @return KingdomVote[] */
    public function getByKingdom(string $kingdomId): array
    {
        return array_values(array_filter($this->votes, static fn(KingdomVote $v) => $v->kingdomId === $kingdomId));
    }

    /** @return KingdomVote[] */
    public function filter(?string $kingdomId = null, ?KingdomVoteType $type = null, ?KingdomVoteStatus $status = null): array
    {
        return array_values(array_filter($this->getByKingdom($kingdomId), static function (KingdomVote $v) use ($kingdomId, $type, $status): bool {
            if ($type !== null && $v->type !== $type) return false;
            if ($status !== null && $v->status !== $status) return false;
            return true;
        }));
    }

    /**
     * @throws TargetAlreadyVotedException
     */
    public function create(CreateKingdomVotePayload $payload): Generator
    {
        /** @var int $insertId */
        $insertId = yield from $this->repo()->create($payload);

        if ($insertId === 0){
            throw new TargetAlreadyVotedException();
        }

        try {
            $row = yield from $this->repo()->getById(new IdPayload($insertId));

            if (is_array($row) && ! empty($row)) {
                try {
                    $row  = $row[0];
                    $vote = KingdomVote::fromArray($row);
                    $this->votes[$vote->id] = $vote;
                    
                    $this->notifyVoteCreated($vote);
                } catch (Throwable $e) {Utils::onFailure($e, null, "Failed to load vote by insert id $insertId from creation: " . $e->getMessage());}
            }
        } catch (Throwable $e) {
            $this->main->getLogger()->warning("KINGDOM VOTES - create: partial refresh failed (#$insertId): " . $e->getMessage());
            $this->syncLoadAll();
        }

        return $insertId;
    }

    /**
     * @throws KingdomVoteAlreadySentException
     */
    public function cast(int $voteId, string $uuid, string $name, bool $voteFor): Generator
    {
        [, $affectedRows] = yield from $this->repo()->cast(new CastKingdomVotePayload($voteId, $uuid, $name, $voteFor));

        if ($affectedRows <= 0){
            throw new KingdomVoteAlreadySentException("You have already voted for vote $voteId");
        }

        $this->notifyMemberVoted($voteId, $name, $voteFor);

        return yield from $this->refreshVote($voteId);
    }

    public function refreshVote(int $voteId, ?Closure $after = null): Generator
    {
        $after ??= static function (?KingdomVote $v): void {};

        $rows = yield from $this->repo()->countVotes(new IdPayload($voteId));
        /** @var array{votes_for:int, votes_against:int, total:int}|null $counts */
        $counts = $rows[0] ?? null;

        $vote = $this->votes[$voteId] ?? null;
        if ($vote === null) {
            try {
                $row = yield from $this->repo()->getById(new IdPayload($voteId));
                if (is_array($row) && !empty($row)) {
                    $row = $row[0];
                    $vote = KingdomVote::fromArray($row);
                    $this->votes[$vote->id] = $vote;
                }
            } catch (Throwable $e) {
                Utils::onFailure($e, null, "Failed to load vote by refresh $voteId from refresh: " . $e->getMessage());
            }
        }

        if ($vote !== null) {
            if (is_array($counts) && $vote->votesFor !== (int) ($counts['votes_for'] ?? 0) || $vote->votesAgainst !== (int) ($counts['votes_against'] ?? 0)) {
                $vote->votesFor     = (int) ($counts['votes_for'] ?? 0);
                $vote->votesAgainst = (int) ($counts['votes_against'] ?? 0);

                yield from $this->repo()->updateVotes(new UpdateKingdomVoteVotes($voteId, $vote->votesFor, $vote->votesAgainst));
            }

            $now = time();
            if ($vote->isExpired($now) && $vote->status === KingdomVoteStatus::Active) {
                $result = $this->determineVoteResult($vote);
                if ($result === true) {
                    yield from $this->updateStatus($vote->id, KingdomVoteStatus::Passed);
                    $vote->status = KingdomVoteStatus::Passed;
                    $this->notifyVoteResult($vote, true);

                    Await::g2c(
                        $vote->handleSuccess($this->main),
                        function () {},
                        fn(Throwable $e) => Utils::onFailure($e, null, "Failed to handle vote success #$vote->id: " . $e->getMessage())
                    );
                } else {
                    yield from $this->updateStatus($vote->id, KingdomVoteStatus::Failed);
                    $vote->status = KingdomVoteStatus::Failed;
                    $this->notifyVoteResult($vote, false);
                }
                
                $this->notificationManager->cleanupReminders($voteId);
            }
        }

        $after($vote);
        return $vote;
    }

    public function updateStatus(int $voteId, KingdomVoteStatus $status): Generator
    {
        return yield from $this->repo()->updateStatus(new UpdateKingdomVoteStatusPayload($voteId, $status->value));
    }

    /**
     * @param int $voteId
     * @param string $name
     * @return Generator<null|string>
     */
    public function getVoterChoice(int $voteId, string $name): Generator
    {
        return yield from $this->repo()->getVoterChoice(new GetKingdomVoterChoicePayload($voteId, $name));
    }

    private function determineVoteResult(KingdomVote $vote): ?bool
    {
        $kingdom = $this->main->getKingdomManager()->getKingdomById($vote->kingdomId);
        if ($kingdom === null) {
            return null;
        }
        
        return $this->quorumManager->determineVoteResult($kingdom, $vote);
    }

    private function notifyVoteCreated(KingdomVote $vote): void
    {
        $kingdom = $this->main->getKingdomManager()->getKingdomById($vote->kingdomId);
        if ($kingdom === null) {
            return;
        }
        
        $this->notificationManager->notifyVoteCreated($kingdom, $vote);
    }

    private function notifyMemberVoted(int $voteId, string $voterName, bool $voteFor): void
    {
        $vote = $this->votes[$voteId] ?? null;
        if ($vote === null) {
            return;
        }
        
        $kingdom = $this->main->getKingdomManager()->getKingdomById($vote->kingdomId);
        if ($kingdom === null) {
            return;
        }
        
        $this->notificationManager->notifyMemberVoted($kingdom, $vote, $voterName, $voteFor);
    }

    private function notifyVoteResult(KingdomVote $vote, bool $passed): void
    {
        $kingdom = $this->main->getKingdomManager()->getKingdomById($vote->kingdomId);
        if ($kingdom === null) {
            return;
        }
        
        $this->notificationManager->notifyVoteResult($kingdom, $vote, $passed);
    }

    public function getVoteQuorumStatistics(int $voteId): ?VoteStatisticsDTO
    {
        $vote = $this->votes[$voteId] ?? null;
        if ($vote === null) {
            return null;
        }
        
        $kingdom = $this->main->getKingdomManager()->getKingdomById($vote->kingdomId);
        if ($kingdom === null) {
            return null;
        }
        
        return $this->quorumManager->getVoteStatistics($kingdom, $vote);
    }

    private function initVoteHandlers(): void
    {
        $this->registerVoteHandler(new VoteKickHandler());
    }

    private function registerVoteHandler(VoteHandlerInterface $handler): void
    {
        $this->voteHandlers[$handler->getType()->value] = $handler;
    }

    public function getVoteHandler(KingdomVoteType $type): ?VoteHandlerInterface
    {
        return $this->voteHandlers[$type->value] ?? null;
    }
}