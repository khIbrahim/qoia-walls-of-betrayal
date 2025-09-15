<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\StoreMember;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\RawStringArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\constraint\InGameRequiredConstraint;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class StoreMemberInfoSubCommand extends WSubCommand
{
    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("player", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        assert($sender instanceof Player);

        $targetName = $args["player"] ?? $sender->getName();
        
        // If targeting another player, check permissions
        if ($targetName !== $sender->getName()) {
            Await::g2c(
                $this->main->getStoreMemberManager()->hasPermission($sender, 'store.members.view'),
                function (bool $hasPermission) use ($sender, $targetName) {
                    if (!$hasPermission) {
                        $sender->sendMessage("§cVous n'avez pas la permission de voir les informations d'autres membres.");
                        return;
                    }
                    $this->showMemberInfo($sender, $targetName);
                },
                function (\Throwable $error) use ($sender) {
                    $sender->sendMessage("§cVous n'avez pas la permission de voir les informations d'autres membres.");
                }
            );
        } else {
            $this->showMemberInfo($sender, $targetName);
        }
    }

    private function showMemberInfo(Player $sender, string $targetName): void
    {
        $targetPlayer = Server::getInstance()->getPlayerByPrefix($targetName);
        if (!$targetPlayer) {
            $sender->sendMessage("§cJoueur non trouvé ou hors ligne.");
            return;
        }

        Await::g2c(
            $this->main->getStoreMemberManager()->getMemberByPlayer($targetPlayer),
            function ($member) use ($sender, $targetPlayer) {
                if (!$member) {
                    $sender->sendMessage("§c{$targetPlayer->getName()} n'est pas membre du store.");
                    return;
                }

                $info = $member->getFormattedInfo();
                foreach ($info as $line) {
                    $sender->sendMessage($line);
                }

                // Show active session if any
                Await::g2c(
                    $this->main->getStoreMemberManager()->isStoreMember($targetPlayer),
                    function ($isActive) use ($sender, $member) {
                        if ($isActive) {
                            $sender->sendMessage("§7Statut actuel: §aEn ligne et actif");
                        }
                    },
                    function (\Throwable $error) {
                        // Ignore error
                    }
                );
            },
            function (\Throwable $error) use ($sender, $targetPlayer) {
                $sender->sendMessage("§cErreur lors de la récupération des informations de {$targetPlayer->getName()}: " . $error->getMessage());
            }
        );
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STORE_MEMBER_INFO);
    }
}