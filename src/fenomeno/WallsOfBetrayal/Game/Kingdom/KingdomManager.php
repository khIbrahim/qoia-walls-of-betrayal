<?php

namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Database\Payload\Kingdom\LoadKingdomPayload;
use fenomeno\WallsOfBetrayal\DTO\KingdomEnchantment;
use fenomeno\WallsOfBetrayal\Entities\Types\PortalEntity;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Manager\Kingdom\KingdomSanctionManager;
use fenomeno\WallsOfBetrayal\Utils\EntityFactoryUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\entity\EntityDataHelper;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use Throwable;

class KingdomManager
{

    /** @var Kingdom[] */
    private array $kingdoms;
    private Config $config;

    private KingdomSanctionManager $sanctionManager;

    public function __construct(private readonly Main $main)
    {
        $this->main->saveResource('kingdoms.yml', true);
        $this->config = new Config($this->main->getDataFolder() . 'kingdoms.yml', Config::YAML);

        $this->sanctionManager = new KingdomSanctionManager($main);

        $this->loadKingdoms();

        foreach ($this->kingdoms as $kingdom){
            $portalId = $kingdom->portalId;
            if($portalId !== ""){
                EntityFactoryUtils::registerEntity(PortalEntity::class, $portalId, static function (World $world, CompoundTag $nbt) use ($portalId): PortalEntity {
                    return new PortalEntity(EntityDataHelper::parseLocation($nbt, $world), $portalId, $nbt);
                });
            }
        }

        $this->main->getServer()->getPluginManager()->registerEvents(new KingdomEvents(), $this->main);
        $this->main->getLogger()->info(TextFormat::GREEN . count($this->kingdoms) . " kingdoms loaded §6(" . implode(", ", array_map(fn(Kingdom $kingdom) => $kingdom->displayName, $this->kingdoms)) . "§6)");
    }

    private function loadKingdoms(): void
    {
        foreach ($this->config->getAll() as $id => $kingdomData) {
            try {
                $id = (string) $id;
                if (! isset($kingdomData['display_name'], $kingdomData['color'], $kingdomData['icon'], $kingdomData['description'], $kingdomData['abilities'])) {
                    $this->main->getLogger()->error("Failed to load kingdom $id, data missing, verify the config in resources/kingdoms.yml");
                    continue;
                }

                $displayName = (string) $kingdomData['display_name'];
                $item = StringToItemParser::getInstance()->parse((string ) $kingdomData['icon']) ?? VanillaItems::PAPER();
                $item->setCustomName($displayName);
                $item->setLore((array) $kingdomData['description']);
                $abilities = array_filter((array) $kingdomData['abilities'] ?? [], fn($abilityId) => is_string($abilityId) && $this->main->getAbilityManager()->getAbilityById($abilityId) !== null);
                $this->main->getLogger()->info("Abilities of : $displayName are §6(" . implode(', ', $abilities) . ")");

                $enchantments = [];
                foreach ((array) ($kingdomData['enchantments'] ?? []) as $enchantmentData) {
                    try {
                        $enchantment = KingdomEnchantment::fromArray($enchantmentData);
                        $enchantments[] = $enchantment;
                    } catch (Throwable $e) {
                        $this->main->getLogger()->error("§cFailed to load enchantment for kingdom $id: " . $e->getMessage());
                    }
                }

                $kingdom = new Kingdom(
                    id: $id,
                    displayName: $displayName,
                    color: (string) $kingdomData['color'],
                    description: implode("\n", (array) $kingdomData['description']),
                    item: $item,
                    abilities: $abilities,
                    portalId: $kingdomData['portal'] ?? "",
                    enchantments: $enchantments
                );

                $this->kingdoms[$id] = $kingdom;

                $this->main->getDatabaseManager()->getKingdomRepository()->load(new LoadKingdomPayload($id));
            } catch (Throwable $e){
                $this->main->getLogger()->error("§cFailed to load kingdom $id (verify the config in resources/kingdoms.yml): " . $e->getMessage());
            }
        }
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

    public function getKingdomByPortalId(string $portalId): ?Kingdom
    {
        foreach ($this->kingdoms as $kingdom){
            if($kingdom->portalId === $portalId){
                return $kingdom;
            }
        }

        return null;
    }

    public function getSanctionManager(): KingdomSanctionManager
    {
        return $this->sanctionManager;
    }

    public function isPlayerSanctioned(string $kingdomId, string $playerUuid): bool
    {
        return $this->sanctionManager->isSanctioned($kingdomId, $playerUuid);
    }

    public function addSanction(Kingdom $kingdom, string $targetName, string $reason, ?int $expiresAt, string $staff = "Kingdom"): Generator
    {
        try {
            [$uuid, $username] = yield from $this->main->getDatabaseManager()->getPlayerRepository()->getUuidAndUsernameByName($targetName);

            $sanctionId = yield from $this->sanctionManager->addSanction(
                $uuid,
                $username,
                $kingdom->id,
                $expiresAt,
                $reason,
                $staff
            );

            return $sanctionId !== false;
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to add sanction for player $targetName in kingdom $kingdom->id");
            return false;
        }
    }

    public function removeSanction(Kingdom $kingdom, string $targetName): Generator
    {
        try {
            [$uuid,] = yield from $this->main->getDatabaseManager()->getPlayerRepository()->getUuidAndUsernameByName($targetName);

            return yield from $this->sanctionManager->removeSanction($kingdom->id, $uuid);
        } catch (Throwable $e) {
            Utils::onFailure($e, null, "Failed to remove sanction for player $targetName in kingdom {$kingdom->id}");
            return false;
        }
    }

    public function kickMember(Kingdom $kingdom, string $targetName, string $reason, ?int $expiresAt, string $staff = "System"): Generator
    {
        if ($expiresAt === null) {
            $expiresAt = time() + (7 * 24 * 60 * 60);
        }

        return yield from $this->addSanction($kingdom, $targetName, $reason, $expiresAt, $staff);
    }

    public function banMember(Kingdom $kingdom, string $targetName, string $reason, string $staff = "System"): Generator
    {
        return yield from $this->addSanction($kingdom, $targetName, $reason, null, $staff);
    }
}