<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KitRequirementRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\IncrementKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\InsertKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\LoadKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\SqlQueriesFileManager;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\SqlError;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use Throwable;

class KitRequirementRepository implements KitRequirementRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_KIT_REQUIREMENT, [], function (){
            $this->main->getLogger()->info("§aTable `kit_requirement` has been successfully init");
        });
    }

    public function load(LoadKitRequirementPayload $payload): Promise
    {
        $resolver = new PromiseResolver();

        $this->main->getDatabaseManager()->executeSelect(Statements::LOAD_KIT_REQUIREMENT, $payload->jsonSerialize(), function (array $rows) use ($resolver) {
            $requirements = [];
            if (empty($rows)){
                $resolver->resolve([]);
                return;
            }
            foreach ($rows as $row){
                if(! isset($row['id'], $row['amount'])){
                    $resolver->reject();
                    continue;
                }

                $requirements[(int) $row['id']] = (int) $row['amount'];
            }

            $resolver->resolve($requirements);
        }, function(SqlError $err) use ($payload) {
            $this->main->getLogger()->error("An error occurred while loading kit requirements data (" . implode(", ", $payload->jsonSerialize()) .") : " . $err->getMessage());
        });

        return $resolver->getPromise();
    }

    /**
     * Je call ça sur un foreach de tout les requirements
     *
     * @param InsertKitRequirementPayload $payload
     * @param \Closure|null $onSuccess
     * @param \Closure|null $onFailure
     * @return void
     */
    public function insert(InsertKitRequirementPayload $payload, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void
    {
        Await::f2c(function () use ($onSuccess, $onFailure, $payload) {
            try {
                yield from $this->main->getDatabaseManager()->asyncInsert(Statements::INSERT_KIT_REQUIREMENT, $payload->jsonSerialize());
                $onSuccess();
            } catch (Throwable $e){
                $onFailure($e);
            }
        });
    }

    public function increment(IncrementKitRequirementPayload $payload, \Closure $onSuccess, \Closure $onFailure): void
    {
        Await::f2c(function () use ($onSuccess, $onFailure, $payload) {
            try {
                yield from $this->main->getDatabaseManager()->asyncChange(Statements::INCREMENT_KIT_REQUIREMENT, $payload->jsonSerialize());
                $onSuccess();
            } catch (Throwable $e){
                $onFailure($e);
            }
        });
    }

    /**
     * NOTE : POUR OPTI LES PERF, C QUE POUR MYSQL !!!!!!!!!!!!!!
     *
     * @param array $updates
     * @return void
     */
    public function batchIncrement(array $updates): void
    {
        if(empty($updates)){
            return;
        }

        $placeholders = implode(", ", array_fill(0, count($updates), "(?, ?, ?, ?)"));

        $params = [];
        foreach ($updates as $u) {
            $params[] = $u['id'];
            $params[] = $u['kingdom'];
            $params[] = $u['kit'];
            $params[] = $u['delta'];
        }

        $sql = "
        INSERT INTO kit_requirement (id, kingdom_id, kit_id, amount)
        VALUES {$placeholders}
        ON DUPLICATE KEY UPDATE
          amount = amount + VALUES(amount)
    ";

        $this->main->getDatabaseManager()->executeRawQuery($sql, $params);
    }

    public static function getQueriesFiles(): array
    {
        return [
            SqlQueriesFileManager::MYSQL => [
                'queries/mysql/kit_requirements.sql'
            ]
        ];
    }
}