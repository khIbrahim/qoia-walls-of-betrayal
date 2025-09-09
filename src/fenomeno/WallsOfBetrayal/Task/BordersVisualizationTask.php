<?php

namespace fenomeno\WallsOfBetrayal\Task;

use fenomeno\WallsOfBetrayal\Utils\SeeKingdomBordersUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class BordersVisualizationTask extends Task
{
    private Player $player;

    /** @var array<string, array<Vector3>> */
    private array $kingdomBorders;

    private int $borderHeight;

    public function __construct(Player $player, array $kingdomBorders, int $borderHeight)
    {
        $this->player         = $player;
        $this->kingdomBorders = $kingdomBorders;
        $this->borderHeight   = $borderHeight;
    }

    public function onRun(): void
    {
        if (! $this->player->isOnline()) {
            $this->getHandler()?->cancel();
            return;
        }

        SeeKingdomBordersUtils::showBorderParticles(
            $this->player,
            $this->kingdomBorders,
            $this->borderHeight
        );
    }
}
