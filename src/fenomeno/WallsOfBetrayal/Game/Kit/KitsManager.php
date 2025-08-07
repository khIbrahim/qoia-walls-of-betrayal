<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use Closure;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class KitsManager
{

    /** @var Kit[] */
    private array $kits = [];
    private Config $config;

    public function __construct(private readonly Main $main)
    {
        $this->main->saveResource('kits.yml', true);
        $this->config = new Config($this->main->getDataFolder() . 'kits.yml', Config::YAML);

        $this->load(function (array $kits){
            $this->kits = $kits;
            $this->main->getLogger()->info(TextFormat::GREEN . count($kits) . " kits loaded §6(" . implode(", ", array_map(fn(Kit $kit) => $kit->getDisplayName(), $kits)) . "§6)");
        });
    }

    private function load(Closure $onSuccess): void
    {
        $kits = [];
        foreach ($this->config->getAll() as $kitId => $kitData) {
            try {
                $kitId     = (string) $kitId;
                $kingdomId = (string) ($kitData['kingdom'] ?? 'null');
                $kingdom   = $this->main->getKingdomManager()->getKingdomById($kingdomId);
                if ($kingdom === null){
                    $this->main->getLogger()->error("Error while parsing $kitId kit: unknown kingdom id ($kingdomId)");
                    continue;
                }

                if (! isset($kitData['id'], $kitData['displayName'], $kitData['description'], $kitData['unlock_day'], $kitData['icon'], $kitData['contents'], $kitData['contents']['inv'], $kitData['contents']['armor'], $kitData['requirements'])) {
                    $this->main->getLogger()->error(TextFormat::RESET . "Failed to load kit $kitId, data missing, verify the config in resources/kits.yml");
                    continue;
                }

                /**
                 * TODO abilities
                 * TODO kits
                 */

                $displayName = (string) $kitData['displayName'];
                $description = (string) $kitData['description'];
                $unlockDay   = (int)    $kitData['unlock_day'];
                $inv         = Utils::loadItems($kitData['contents']['inv']);
                $armor       = Utils::loadItems($kitData['contents']['armor']);

                $item = StringToItemParser::getInstance()->parse((string ) $kitData['icon']) ?? VanillaItems::PAPER();

                $kit = new Kit(
                    id: $kitId,
                    kingdom: $kingdom,
                    displayName: $displayName,
                    description: $description,
                    unlockDay: $unlockDay,
                    item: $item,
                    inv: $inv,
                    armor: $armor,
                );

                $kits[$kitId] = $kit;
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load kit $kitId for kingdom id $kingdomId (verify the config in resources/kits.yml): " . $e->getMessage());
            }
        }

        $onSuccess($kits);
    }

    public function getKitById(string $kitId): ?Kit
    {
        return $this->kits[$kitId] ?? null;
    }

    /** @return Kit[] */
    public function getKits(): array
    {
        return $this->kits;
    }

}