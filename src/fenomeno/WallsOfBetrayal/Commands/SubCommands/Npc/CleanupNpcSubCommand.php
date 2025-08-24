<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\command\CommandSender;
use Throwable;

class CleanupNpcSubCommand extends WSubCommand
{

    protected function prepare(): void
    {

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("§6Clean server from npcs...");

        Await::g2c(
            $this->cleanup($sender),
            function (array $result) use ($sender) {
                [$safe, $forced] = $result;
                $sender->sendMessage("§aCleaned $safe safe npc, $forced forced npc.");
            },
            fn (Throwable $e) => Utils::onFailure($e, $sender, "Failed to clean npc by {$sender->getName()}: " . $e->getMessage())
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NPC_CLEANUP);
    }

    private function cleanup($sender): array|Generator
    {
        $tasks = [];

        foreach ($sender->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if (! $entity instanceof NpcEntity) {
                    continue;
                }

                $tasks[] = (function () use ($entity): \Generator {
                    try {
                        yield from $this->main->getNpcManager()->remove($entity->getNpcId());
                        return [1, 0];
                    } catch (\Throwable) {
                        if (!$entity->isFlaggedForDespawn()) {
                            $entity->flagForDespawn();
                        }
                        return [0, 1];
                    }
                })();
            }
        }

        $results = yield from Await::all($tasks);

        $safe = 0;
        $forced = 0;
        foreach ($results as [$s, $f]) {
            $safe += $s;
            $forced += $f;
        }

        return [$safe, $forced];
    }
}