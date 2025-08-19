<?php

namespace fenomeno\WallsOfBetrayal\Commands\Player;

use fenomeno\WallsOfBetrayal\Commands\CommandsIds;
use fenomeno\WallsOfBetrayal\Commands\WCommand;
use fenomeno\WallsOfBetrayal\Config\CommandsConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\args\TargetPlayerArgument;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\ArgumentOrderException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Throwable;

class StatsCommand extends WCommand
{

    const PLAYER_ARGUMENT = 'player';

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(self::PLAYER_ARGUMENT, true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerName = $args[self::PLAYER_ARGUMENT] ?? $sender->getName();
        $player     = $sender->getServer()->getPlayerExact($playerName);
        if ($player instanceof Player){
            $session = Session::get($player);
            if(! $session->isLoaded()){
                MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_LOADED, [
                    ExtraTags::PLAYER => $playerName
                ]);
                return;
            }

            $kills = $session->getKills();
            $deaths = $session->getDeaths();
            $kdr = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
            MessagesUtils::sendTo($sender, MessagesIds::PLAYER_STATS, [
                ExtraTags::PLAYER => $playerName,
                ExtraTags::KILLS => $kills,
                ExtraTags::DEATHS => $deaths,
                ExtraTags::KDR => $kdr
            ]);
        } else {
            Await::f2c(function () use ($sender, $playerName) {
                try {
                    /** @var PlayerData $data */
                    $data = yield from $this->main->getDatabaseManager()->getPlayerRepository()->asyncLoad(new UsernamePayload(strtolower($playerName)));

                    $kills  = $data->kills;
                    $deaths = $data->deaths;
                    $kdr    = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
                    MessagesUtils::sendTo($sender, MessagesIds::PLAYER_STATS, [
                        ExtraTags::PLAYER => $playerName,
                        ExtraTags::KILLS  => $kills,
                        ExtraTags::DEATHS => $deaths,
                        ExtraTags::KDR    => $kdr
                    ]);
                } catch (RecordNotFoundException) {
                    MessagesUtils::sendTo($sender, MessagesIds::PLAYER_NOT_FOUND, [ExtraTags::PLAYER => $playerName]);
                } catch (Throwable $e) {
                    $this->main->getLogger()->error("§cFailed to load player data: " . $e->getMessage());
                }
            });
        }
    }

    public function getCommandDTO(): CommandDTO
    {
        return CommandsConfig::getCommandById(CommandsIds::STATS);
    }
}