<?php

namespace fenomeno\WallsOfBetrayal\Game\Kit;

use Closure;
use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\InsertKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\KitRequirement\LoadKitRequirementPayload;
use fenomeno\WallsOfBetrayal\Enum\KitRequirementType;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Game\Kit\RequirementHandlers\RequirementHandlerFactory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class KitsManager
{

    private RequirementHandlerFactory $requirementHandlerFactory;

    /** @var Kit[] */
    private array $kits = [];
    private Config $config;

    public function __construct(private readonly Main $main)
    {
        $this->main->saveResource('kits.yml', true);
        $this->config = new Config($this->main->getDataFolder() . 'kits.yml', Config::YAML);

        $this->requirementHandlerFactory = new RequirementHandlerFactory();

        $this->load(function(array $kits) {
            $this->kits = $kits;

            $kingdoms = $this->main->getKingdomManager()->getKingdoms();

            foreach ($kingdoms as $kingdom) {

                foreach ($this->kits as $kit) {
                    if ($kit->getKingdom()->getId() === $kingdom->getId()) {
                        $kingdom->kits[$kit->getId()] = $kit;

                        $this->main->getDatabaseManager()->getKitRequirementRepository()->load(
                            new LoadKitRequirementPayload($kingdom->getId(), $kit->getId())
                        )->onCompletion(function ($requirements) use ($kit, $kingdom) {
                                if ($requirements === []){
                                    foreach ($kit->getRequirements() as $id => $requirement){
                                        $this->main->getDatabaseManager()->getKitRequirementRepository()->insert(
                                            new InsertKitRequirementPayload($id, $kingdom->getId(), $kit->getId()),
                                            fn() => $this->main->getLogger()->info("§l" . $kingdom->getDisplayName() . " §ainserted kit §6(" . $kit->getDisplayName() . "§6)"),
                                            fn(Throwable $e) => $this->main->getLogger()->error('failed to create requirement ' . $id . ' for kingdom ' . $kingdom->getId() . ' & kit ' . $kit->getId()),
                                        );
                                    }
                                    return;
                                }

                                foreach ($requirements as $id => $requirementProgress){
                                    $requirement = $kit->getRequirement($id);
                                    if ($requirement === null){
                                        $this->main->getLogger()->error("Failed to get kit requirement (id: $id) for kingdom {$kingdom->getId()} & kit {$kit->getId()}");
                                        continue;
                                    }

                                    $requirement->setProgress((int) $requirementProgress);
                                }

                                $this->main->getLogger()->info("§l" . $kingdom->getDisplayName() . " §akit loaded §6(" . $kit->getDisplayName() . "§6)");
                        }, fn() => $this->main->getLogger()->error("Failed to load kit requirements for kingdom {$kingdom->getId()} & kit {$kit->getId()}"));

                    }
                }
            }
        });

        $this->main->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->flushKitRequirements()), 20 * WobConfig::getKitRequirementsFlushInterval());
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
                    $this->main->getLogger()->error("Error while parsing kit $kitId: unknown kingdom id ($kingdomId)");
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

                $displayName  = (string) $kitData['displayName'];
                $description  = (string) $kitData['description'];
                $unlockDay    = (int)    $kitData['unlock_day'];
                $inv          = Utils::loadItems($kitData['contents']['inv']);
                $armor        = Utils::loadItems($kitData['contents']['armor']);
                $item         = StringToItemParser::getInstance()->parse((string ) $kitData['icon']) ?? VanillaItems::PAPER();

                $requirements     = [];
                $requirementsData = (array) $kitData['requirements'];
                foreach ($requirementsData as $requirementId => $requirementData){
                    if(! isset($requirementData['type'], $requirementData['target'], $requirementData['amount'])){
                        $this->main->getLogger()->error("Failed to load requirement $requirementId for kit $kitId: data are missing (type, target, amount)");
                        continue;
                    }

                    $typeValue = (string) $requirementData['type'];
                    $type      = KitRequirementType::tryFrom($typeValue);
                    if ($type === null){
                        $this->main->getLogger()->error("Failed to load requirement $requirementId for kit $kitId: unknown requirement type: " . $typeValue);
                        continue;
                    }

                    $amount = (int) $requirementData['amount'];
                    $target = $requirementData['target'];
                    $requirements[$requirementId] = new KitRequirement(
                        id: $requirementId,
                        kingdomId: $kingdomId,
                        kitId: $kitId,
                        type: $type,
                        target: $target,
                        amount: $amount
                    );
                }

                $kit = new Kit(
                    id: $kitId,
                    kingdom: $kingdom,
                    displayName: $displayName,
                    description: $description,
                    unlockDay: $unlockDay,
                    item: $item,
                    inv: $inv,
                    armor: $armor,
                    requirements: $requirements
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
    public function getKitsByKingdom(?Kingdom $kingdom = null): array
    {
        if ($kingdom === null){
            return $this->kits;
        }

        return array_filter($this->kits, function ($kit) use ($kingdom) {
            return $kit->getKingdom()->getId() === $kingdom->getId();
        });
    }

    public function getRequirementHandlerFactory(): RequirementHandlerFactory
    {
        return $this->requirementHandlerFactory;
    }

    private function flushKitRequirements(): void
    {
        foreach ($this->main->getKingdomManager()->getKingdoms() as $kingdom) {
            $batch = [];
            foreach ($kingdom->getKits() as $kit) {
                foreach ($kit->getRequirements() as $requirement) {
                    $delta = $requirement->consumeDirty();
                    if ($delta > 0) {
                        $batch[] = [
                            'id'      => $requirement->getId(),
                            'kingdom' => $kingdom->getId(),
                            'kit'     => $kit->getId(),
                            'delta'   => $delta,
                        ];
                    }
                }
            }
            if (! empty($batch)) {
                try {
                    $this->main->getDatabaseManager()
                        ->getKitRequirementRepository()
                        ->batchIncrement($batch);
                } catch (Throwable $e) {
                    $this->main->getLogger()->error("Failed to batchIncrement kits for kingdom {$kingdom->getId()}: {$e->getMessage()}");
                }
            }
        }
    }

}