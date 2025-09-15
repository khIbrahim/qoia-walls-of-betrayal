<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StoreMemberClockOutSubCommand extends WSubCommand
{
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        Await::g2c(
            $this->main->getStoreMemberManager()->clockOut($sender),
            function ($session) use ($sender) {
                if ($session) {
                    $sessionInfo = $session->getFormattedSessionInfo();
                    $sender->sendMessage("§aVous avez terminé votre session de travail.");
                    foreach ($sessionInfo as $line) {
                        $sender->sendMessage($line);
                    }
                }
            },
            function (\Throwable $error) use ($sender) {
                $message = match ($error->getMessage()) {
                    "Player is not a store member" => "§cVous n'êtes pas membre du store.",
                    "Member is not clocked in" => "§cVous n'êtes pas en session de travail.",
                    default => "§cErreur: " . $error->getMessage()
                };
                $sender->sendMessage($message);
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER_CLOCKOUT);
    }
}