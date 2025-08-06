<?php

namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use Closure;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionHelper;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class KingdomManager
{

    /** @var Kingdom[] */
    private array $kingdoms = [];
    private Config $config;

    public function __construct(private readonly Main $main)
    {
        $this->main->saveResource('kingdoms.yml', true);
        $this->config = new Config($this->main->getDataFolder() . 'kingdoms.yml', Config::YAML);

        $this->load(function (array $kingdoms){
            $this->kingdoms = $kingdoms;
            $this->main->getLogger()->info(TextFormat::GREEN . count($kingdoms) . " kingdoms loaded §6(" . implode(", ", array_map(fn(Kingdom $kingdom) => $kingdom->displayName, $kingdoms)) . ")");
        });
    }

    private function load(Closure $onSuccess): void
    {
        $kingdoms = [];
        foreach ($this->config->getAll() as $id => $kingdomData) {
            try {
                $id = (string) $id;
                if (! isset($kingdomData['display_name'], $kingdomData['color'], $kingdomData['icon'], $kingdomData['description'], $kingdomData['spawn'])) {
                    $this->main->getLogger()->error(TextFormat::RESET . "Failed to load kingdom $id, data missing, verify the config in resources/kingdoms.yml");
                    continue;
                }

                /**
                 * TODO abilities
                 * TODO kits
                 */

                $displayName = (string) $kingdomData['display_name'];
                $item = StringToItemParser::getInstance()->parse((string ) $kingdomData['icon']) ?? VanillaItems::PAPER();
                $item->setCustomName($displayName);
                $item->setLore((array) $kingdomData['description']);
                $position = PositionHelper::load((array) $kingdomData['spawn']);

                $kingdom = new Kingdom(
                    id: $id,
                    displayName: $displayName,
                    color: (string) $kingdomData['color'],
                    description: implode("\n", (array) $kingdomData['description']),
                    item: $item,
                    spawn: $position
                );
                $kingdoms[$id] = $kingdom;
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load kingdom $id (verify the config in resources/kingdoms.yml): " . $e->getMessage());
            }
        }

        $onSuccess($kingdoms);
    }

    public function getKingdomById(string $kingdom): ?Kingdom
    {
        return $this->kingdoms[$kingdom] ?? null;
    }

    /** @return Kingdom[] */
    public function getKingdoms(): array
    {
        return $this->kingdoms;
    }

}