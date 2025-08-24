<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Npc;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Throwable;

class LoadNpcSubCommand extends WSubCommand
{
    protected function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(TF::GOLD . "Loading NPCs...");

        Await::g2c(
            $this->main->getNpcManager()->asyncLoadFromDatabase(),
            function (array $stats) use ($sender) {
                [$loaded, $already, $worldMissing] = $stats;
                if ($loaded > 0) {
                    $sender->sendMessage(TF::GREEN . "Spawned $loaded NPC(s).");
                }
                if ($already > 0) {
                    $sender->sendMessage(TF::YELLOW . "$already NPC(s) already present, skipped.");
                }
                if ($worldMissing > 0) {
                    $sender->sendMessage(TF::RED . "$worldMissing NPC(s) skipped (world not loaded).");
                }
                if ($loaded === 0 && $already === 0 && $worldMissing === 0) {
                    $sender->sendMessage(TF::RED . "No NPC loaded.");
                }
            },
            fn(Throwable $e) => Utils::onFailure($e, $sender, "failed to load all npcs by {$sender->getName()}: " . $e->getMessage()),
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NPC_LOAD);
    }
}