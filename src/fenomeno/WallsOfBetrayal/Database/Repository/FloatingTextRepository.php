<?php

namespace fenomeno\WallsOfBetrayal\Database\Repository;

use Closure;
use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\FloatingTextRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\CreateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\UpdateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionHelper;
use Generator;
use Throwable;

class FloatingTextRepository implements FloatingTextRepositoryInterface
{

    public function __construct(private readonly Main $main){}

    public function init(DatabaseManager $database): void
    {
        $database->executeGeneric(Statements::INIT_FLOATING_TEXT, [], function (){
            $this->main->getLogger()->info("§aTable `floating_text` has been successfully init");
        });
    }

    public function load(Closure $onSuccess, Closure $onFailure): void
    {
        $this->main->getDatabaseManager()->executeSelect(Statements::LOAD_FLOATING_TEXTS, [], function (array $rows) use ($onSuccess) {
            if (empty($rows)){
                $onSuccess([]);
                return;
            }

            $floatingTexts = [];
            foreach ($rows as $i => $row){
                try {
                    if(! isset($row['id'], $row['text'], $row['pos'])){
                        $this->main->getLogger()->error("Failed to parse floating text $i, data is missing.");
                        continue;
                    }

                    $id       = (string) $row['id'];
                    $text     = str_replace('\n', "\n", (string) $row['text']);
                    $posData  = json_decode($row['pos'], true);
                    $position = PositionHelper::load($posData);

                    $floatingTexts[$id] = new FloatingText(
                        $id,
                        $position,
                        $text,
                    );
                } catch (Throwable $e){
                    $this->main->getLogger()->error("Failed to parse floating text $i: " . $e->getMessage());
                    $this->main->getLogger()->logException($e);
                }
            }

            $onSuccess($floatingTexts);
        }, $onFailure);
    }

    public function create(CreateFloatingTextPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncInsert(Statements::CREATE_FLOATING_TEXT, $payload->jsonSerialize());
    }

    public function remove(IdPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncGeneric(Statements::REMOVE_FLOATING_TEXT, $payload->jsonSerialize());
    }

    public function updateText(UpdateFloatingTextPayload $payload): Generator
    {
        yield from $this->main->getDatabaseManager()->asyncChange(Statements::UPDATE_FLOATING_TEXT, $payload->jsonSerialize());
    }
}