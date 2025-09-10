<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Task\BordersVisualizationTask;
use Generator;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\DustParticle;
use Throwable;

class SeeKingdomBordersUtils
{
    /** @var array<string, array<string, array<Vector3>>> [playerName => [kingdomId => [borderPoints]]] */
    private static array $viewingBorders = [];

    /** @var array<string, TaskHandler> */
    private static array $visualizationTasks = [];

    private const PARTICLE_DENSITY = 1.0;

    private const BORDER_HEIGHT = 15;
    private const BORDER_THICKNESS = 0.2;

    public static function isViewingBorders(string $playerName): bool
    {
        return isset(self::$viewingBorders[strtolower($playerName)]);
    }

    public static function toggleBorderView(Player $player): Generator {
        return Await::promise(function ($resolve, $reject) use($player){
            try {
                if (self::isViewingBorders($player->getName())) {
                    self::removeKingdomBordersFrom($player);
                    $resolve(false);
                } else {
                    self::updateKingdomsBordersFor($player);
                    $resolve(true);
                }
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    public static function updateKingdomsBordersFor(Player $player): void
    {
        $playerName = strtolower($player->getName());
        $kingdoms = Main::getInstance()->getKingdomManager()->getKingdoms();
        self::$viewingBorders[$playerName] = [];

        foreach ($kingdoms as $kingdom) {
            $borders = $kingdom->getBase()->borders;
            $world = $kingdom->getBase()->world;

            if ($world === null || $player->getWorld()->getFolderName() !== $world->getFolderName()) {
                continue;
            }

            self::$viewingBorders[$playerName][$kingdom->getId()] = self::calculateBorderPoints($borders);
        }

        if (! empty(self::$viewingBorders[$playerName])) {
            self::startVisualizationTask($player);
        }
    }

    private static function calculateBorderPoints(AxisAlignedBB $border): array
    {
        $points = [];

        for ($x = $border->minX; $x <= $border->maxX; $x += self::PARTICLE_DENSITY) {
            $points[] = new Vector3($x, 0, $border->minZ);
        }

        for ($z = $border->minZ; $z <= $border->maxZ; $z += self::PARTICLE_DENSITY) {
            $points[] = new Vector3($border->maxX, 0, $z);
        }

        for ($x = $border->maxX; $x >= $border->minX; $x -= self::PARTICLE_DENSITY) {
            $points[] = new Vector3($x, 0, $border->maxZ);
        }

        for ($z = $border->maxZ; $z >= $border->minZ; $z -= self::PARTICLE_DENSITY) {
            $points[] = new Vector3($border->minX, 0, $z);
        }

        return $points;
    }

    private static function startVisualizationTask(Player $player): void
    {
        $playerName = strtolower($player->getName());

        if (isset(self::$visualizationTasks[$playerName])) {
            self::$visualizationTasks[$playerName]->cancel();
        }

        $task = new BordersVisualizationTask($player, self::$viewingBorders[$playerName], self::BORDER_HEIGHT);
        self::$visualizationTasks[$playerName] = Main::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
    }

    public static function removeKingdomBordersFrom(Player $player): void
    {
        $playerName = strtolower($player->getName());

        if (isset(self::$visualizationTasks[$playerName])) {
            self::$visualizationTasks[$playerName]->cancel();
            unset(self::$visualizationTasks[$playerName]);
        }

        unset(self::$viewingBorders[$playerName]);
    }

    public static function showBorderParticles(Player $player, array $kingdomBorders, int $height): void
    {
        if (! $player->isOnline()) {
            self::removeKingdomBordersFrom($player);
            return;
        }

        $playerY = $player->getPosition()->getY();
        $minY    = $playerY - $height;
        $maxY    = $playerY + $height;

        $yPositions = [];
        for ($y = $minY; $y <= $maxY; $y += 3) {
            $yPositions[] = $y;
        }

        foreach ($kingdomBorders as $kingdomId => $borderPoints) {
            $kingdom = Main::getInstance()->getKingdomManager()->getKingdomById($kingdomId);
            if ($kingdom === null) continue;

            $color = $kingdom->getColor();

            foreach ($borderPoints as $point) {
                foreach ($yPositions as $y) {
                    $thickness = self::BORDER_THICKNESS;

                    $particle = new DustParticle($color);

                    $player->getWorld()->addParticle(new Vector3($point->x, $y, $point->z), $particle, [$player]);
                    $player->getWorld()->addParticle(new Vector3($point->x + $thickness, $y, $point->z), $particle, [$player]);
                    $player->getWorld()->addParticle(new Vector3($point->x - $thickness, $y, $point->z), $particle, [$player]);
                    $player->getWorld()->addParticle(new Vector3($point->x, $y, $point->z + $thickness), $particle, [$player]);
                    $player->getWorld()->addParticle(new Vector3($point->x, $y, $point->z - $thickness), $particle, [$player]);
                }
            }
        }
    }

}