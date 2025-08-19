<?php

namespace fenomeno\WallsOfBetrayal\Commands\SubCommands\Nick;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\SubCommands\WSubCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\Services\NickService;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;

class LogSubCommand extends WSubCommand
{

    private const TARGET_ARGUMENT = "player";

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::TARGET_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (isset($args[self::TARGET_ARGUMENT])){
            $target = $args[self::TARGET_ARGUMENT];

            $dataList = NickService::getInstance()->getLog($target);
            if ($dataList === null){
                MessagesUtils::sendTo($sender, MessagesIds::NICK_NOT_SET, [
                    ExtraTags::PLAYER => $target
                ]);
                return;
            }

            foreach ($dataList as $entry){
                MessagesUtils::sendTo($sender, MessagesIds::NICK_LOG_ENTRY, [
                    ExtraTags::PLAYER => $target,
                    ExtraTags::NICK   => $entry["nick"],
                    ExtraTags::DATE   => date("d/m/Y H:i:s", $entry["timestamp"])
                ]);
            }
        } else {
            $logs = NickService::getInstance()->logs();
            if (empty($logs)){
                MessagesUtils::sendTo($sender, MessagesIds::NICK_LOG_EMPTY);
                return;
            }

            MessagesUtils::sendTo($sender, MessagesIds::NICK_LOG_LIST_HEADER);
            foreach ($logs as $playerName => $entries){
                foreach ($entries as $entry){
                    MessagesUtils::sendTo($sender, MessagesIds::NICK_LOG_ENTRY, [
                        ExtraTags::PLAYER => $playerName,
                        ExtraTags::NICK   => $entry["nick"],
                        ExtraTags::DATE   => date("d/m/Y H:i:s", $entry["timestamp"])
                    ]);
                }
            }
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::NICK_LOG);
    }
}