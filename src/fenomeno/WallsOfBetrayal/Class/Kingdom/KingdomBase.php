<?php

namespace fenomeno\WallsOfBetrayal\Class\Kingdom;

use fenomeno\WallsOfBetrayal\Exceptions\Kingdom\KingdomWorldNotFoundException;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class KingdomBase
{

    private array $playersInBase = [];

    public function __construct(
        public AxisAlignedBB $borders,
        public ?World        $world = null
    ){}

    /**
     * @throws KingdomWorldNotFoundException
     */
    public static function fromArray(array $data): KingdomBase
    {
        if(isset($data['world'])){
            $worldName    = (string) $data['world'];
            $worldManager = Server::getInstance()->getWorldManager();
            $worldManager->loadWorld($worldName);

            $world = $worldManager->getWorldByName($worldName);
            if ($world === null) {
                throw new KingdomWorldNotFoundException($worldName . " world not found");
            }
        } else {
            $world = Main::getInstance()->getServerManager()->getKingdomsWorld();
        }

        $borders = new AxisAlignedBB(
            (float) ($data['minX'] ?? 0),
            (float) ($data['minY'] ?? 0),
            (float) ($data['minZ'] ?? 0),
            (float) ($data['maxX'] ?? 0),
            (float) ($data['maxY'] ?? 0),
            (float) ($data['maxZ'] ?? 0)
        );

        return new KingdomBase($borders, $world);
    }

    public function toArray(): array
    {
        return [
            'world' => $this->world->getFolderName(),
            'minX'  => $this->borders->minX,
            'minY'  => $this->borders->minY,
            'minZ'  => $this->borders->minZ,
            'maxX'  => $this->borders->maxX,
            'maxY'  => $this->borders->maxY,
            'maxZ'  => $this->borders->maxZ,
        ];
    }

    public function isDefined(): bool
    {
        return ! Utils::isAABBOne($this->borders) && $this->world !== null;
    }

    public function getCenter(): Position
    {
        $centerX = ($this->borders->minX + $this->borders->maxX) / 2;
        $centerY = ($this->borders->minY + $this->borders->maxY) / 2;
        $centerZ = ($this->borders->minZ + $this->borders->maxZ) / 2;
        return new Position($centerX, $centerY, $centerZ, $this->world);
    }

    public function addPlayerInBase(Player $player): void
    {
        $this->playersInBase[$player->getName()] = $player;
    }

    public function removePlayerFromBase(Player $player): void
    {
        unset($this->playersInBase[$player->getName()]);
    }

    public function isPlayerInBase(Player $player): bool
    {
        return isset($this->playersInBase[$player->getName()]);
    }

    public function contains(Position $position): bool
    {
        if($this->world && strtolower($position->world->getFolderName()) !== strtolower($this->world->getFolderName())){
            return false;
        }

        [$min, $max] = $this->normalizeBorders();

        return
            $position->x >= $min->x && $position->x <= $max->x &&
            $position->y >= $min->y && $position->y <= $max->y &&
            $position->z >= $min->z && $position->z <= $max->z;
    }

    private function normalizeBorders(): array
    {
        return [
            new Vector3(min($this->borders->minX, $this->borders->maxX), min($this->borders->minY, $this->borders->maxY), min($this->borders->minZ, $this->borders->maxZ)),
            new Vector3(max($this->borders->minX, $this->borders->maxX), max($this->borders->minY, $this->borders->maxY), max($this->borders->minZ, $this->borders->maxZ))
        ];
    }

}