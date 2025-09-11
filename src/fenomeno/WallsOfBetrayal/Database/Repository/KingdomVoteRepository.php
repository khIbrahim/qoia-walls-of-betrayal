<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Class\Kingdom\KingdomVote;
use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\CastKingdomVotePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\CreateKingdomVotePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\GetKingdomVoterChoicePayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\UpdateKingdomVoteStatusPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\Vote\UpdateKingdomVoteVotes;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use Throwable;

final class KingdomVoteRepository implements RepositoryInterface
{
    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_KINGDOM_VOTES, [], fn() => $this->main->getLogger()->info("§aTable `kingdom_votes` has been successfully init"));
        $database->executeGeneric(Statements::INIT_KINGDOM_VOTE_VOTES, [], fn() => $this->main->getLogger()->info("§aTable `kingdom_vote_votes` has been successfully init"));
    }

    public function load(Closure $onSuccess, Closure $onFailure): void
    {
        $this->main->getDatabaseManager()->executeSelect(Statements::LOAD_KINGDOM_VOTES, [], function (array $rows) use ($onSuccess) {
            if (empty($rows)){
                $onSuccess([]);
            }

            $votes = [];
            foreach ($rows as $i => $row){
                try {
                    $vote = KingdomVote::fromArray($row);
                    $votes[$vote->id] = $vote;
                } catch (Throwable $e){
                    $this->main->getLogger()->error("Failed to parse vote data ($i): " . $e->getMessage());
                }
            }

            $onSuccess($votes);
        }, $onFailure);
    }

    public function create(CreateKingdomVotePayload $payload): Generator
    {
        [$insertId, ] = yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CREATE_KINGDOM_VOTE, $payload->jsonSerialize());

        return $insertId;
    }

    public function cast(CastKingdomVotePayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CAST_KINGDOM_VOTE, $payload->jsonSerialize());
    }

    public function updateStatus(UpdateKingdomVoteStatusPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_KINGDOM_VOTE_STATUS, $payload->jsonSerialize());
    }

    public function countVotes(IdPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncSelect(Statements::COUNT_KINGDOM_VOTE_VOTES, $payload->jsonSerialize());
    }

    public function getById(IdPayload $payload): Generator
    {
        return yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_KINGDOM_VOTE, $payload->jsonSerialize());
    }

    /**
     * @param GetKingdomVoterChoicePayload $payload
     * @return Generator<bool|null>
     */
    public function getVoterChoice(GetKingdomVoterChoicePayload $payload): Generator
    {
        $result = yield from $this->main->getDatabaseManager()->asyncSelect(Statements::GET_KINGDOM_VOTER_CHOICE, $payload->jsonSerialize());

        if (empty($result)) {
            return null;
        }

        $row = $result[0];
        if (empty($row)) {
            return null;
        }

        return (bool) $row['vote_for'];
    }

    public function updateVotes(UpdateKingdomVoteVotes $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_KINGDOM_VOTE_VOTES, $payload->jsonSerialize());
    }

    public function deleteExpired(): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::DELETE_EXPIRED_KINGDOM_VOTES);
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/kingdom_votes.sql',
                'queries/mysql/kingdom_vote_votes.sql',
            ]
        ];
    }
}
