<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\scheduler\Task;
use Throwable;

/**
 * Repeating task of 20 ticks
 */
class SessionTask extends Task
{

    private const FLUSH_INTERVAL_TICKS = 10;
    private int $ticks = 0;

    public function __construct(private readonly Session $session){}


    public function onRun(): void
    {
        if(! $this->session->getPlayer()->isOnline()){
            $this->getHandler()->cancel();
            return;
        }

        if(! $this->session->isLoaded()){
            return;
        }

        if(++$this->ticks < self::FLUSH_INTERVAL_TICKS){
            return;
        }

        Await::f2c(function (){
            try {
                $flushTasks = [
                    $this->session->flushStats(),
                    $this->session->getSeasonPlayer()?->flushStats()
                ];

                // Ajouter le flush des stats du royaume si disponible
                $kingdom = $this->session->getKingdom();
                if ($kingdom !== null && $kingdom->isSeasonDataLoaded()) {
                    $flushTasks[] = $kingdom->flushSeasonStats();
                }

                $results = yield from Await::all($flushTasks);

                // Log seulement si au moins une flush a réussi
                $anySuccess = array_filter($results, fn($result) => $result === true);
                if (!empty($anySuccess)) {
                    var_dump($this->session->getPlayer()->getDisplayName() . " flush");
                }
            } catch (Throwable $e){
                Utils::onFailure($e, $this->session->getPlayer(), "Failed to flush player and season stats");
            }
        });

        $this->ticks = 0;
    }
}