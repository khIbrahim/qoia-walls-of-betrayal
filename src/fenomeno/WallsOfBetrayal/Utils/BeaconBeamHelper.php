<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;

final class BeaconBeamHelper
{

    public static function spawnBeaconBeam(
        PluginBase $plugin,
        Position   $beaconPos,
        int        $height = 16,
        Color      $color = new Color(0, 255, 180),
        int        $tickInterval = 2,
        bool       $withHalo = true,
        float      $haloRadius = 0.45,
        int        $durationSeconds = 60,   // ⬅️ en secondes (met -1 pour infini)
        ?array     $onlyFor = null
    ): TaskHandler
    {

        $world = $beaconPos->getWorld();
        $base = new Vector3($beaconPos->x + 0.5, $beaconPos->y + 1, $beaconPos->z + 0.5);
        $particleCore = new DustParticle($color);

        $haloAngles = [];
        if ($withHalo) {
            $steps = 6;
            for ($i = 0; $i < $steps; $i++) {
                $haloAngles[] = (2 * M_PI * $i) / $steps;
            }
        }

        $age = 0;
        $task = new ClosureTask(function () use ($world, $base, $height, $particleCore, $haloAngles, $haloRadius, $onlyFor, &$age): void {
            $pulse = 1 + (int)(1.5 * (0.5 + 0.5 * sin($age / 8)));

            for ($y = 0.0; $y <= $height; $y += 0.5) {
                $pos = $base->add(0.0, $y, 0.0);

                for ($k = 0; $k < $pulse; $k++) {
                    if ($onlyFor !== null) {
                        $world->addParticle($pos, $particleCore, $onlyFor);
                    } else {
                        $world->addParticle($pos, $particleCore);
                    }
                }

                if (!empty($haloAngles)) {
                    foreach ($haloAngles as $ang) {
                        $hx = $haloRadius * cos($ang);
                        $hz = $haloRadius * sin($ang);
                        $hpos = $pos->add($hx, 0.0, $hz);
                        if ($onlyFor !== null) {
                            $world->addParticle($hpos, $particleCore, $onlyFor);
                        } else {
                            $world->addParticle($hpos, $particleCore);
                        }
                    }
                }
            }

            $age++;
        });

        $handler = $plugin->getScheduler()->scheduleRepeatingTask($task, $tickInterval);

        if ($durationSeconds >= 0) {
            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($handler): void {
                if (!$handler->isCancelled()) {
                    $handler->cancel();
                }
            }), $durationSeconds * 20);
        }

        return $handler;
    }
}