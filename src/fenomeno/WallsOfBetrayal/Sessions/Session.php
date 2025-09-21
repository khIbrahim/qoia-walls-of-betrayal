<?php
namespace fenomeno\WallsOfBetrayal\Sessions;

use fenomeno\WallsOfBetrayal\Class\Player\PlayerLoyalty;
use fenomeno\WallsOfBetrayal\Class\Season\SeasonPlayer;
use fenomeno\WallsOfBetrayal\Database\Payload\Abstract\UuidPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\InsertPlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerAbilities;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerStatsPayload;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Handlers\PlayerJoinHandler;
use fenomeno\WallsOfBetrayal\Inventory\ChooseKingdomInventory;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Task\SessionTask;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Throwable;
use WeakMap;

class Session
{
    /** @var WeakMap<Player, Session> Map des sessions par joueur */
    private static WeakMap $map;

    private array $abilities = [];

    public static function get(Player $player) : Session
    {
        if (! isset(self::$map)) {
            self::$map = new WeakMap();
        }

        return self::$map[$player] ??= new self($player);
    }

    private bool $loaded = false;

    private bool $dirty = false;

    private ?Kingdom $kingdom = null;

    private bool $choosingKingdom = false;

    private int $kills = 0;

    private int $deaths = 0;

    private ?SeasonPlayer $seasonPlayer = null;
    private ?PlayerLoyalty $loyalty = null;

    private bool $frozen = false;


    public function __construct(private readonly Player $player) {}


    public function load(): void
    {
        $this->player->setNoClientPredictions();
        $this->player->sendTitle("§7Loading your data...");

        $playerUuid = $this->player->getUniqueId()->toString();
        $playerName = strtolower($this->player->getName());

        $main          = Main::getInstance();
        $currentSeason = $main->getSeasonManager()->getCurrentSeason();

        Await::f2c(function () use ($currentSeason, $main, $playerUuid, $playerName) {
            try {
                /**
                 * @var PlayerData $playerData
                 * @var SeasonPlayer|null $seasonData
                 */
                [$playerData, $seasonData] = yield from Await::all([
                    $main->getDatabaseManager()->getPlayerRepository()->load(new LoadPlayerPayload($playerUuid, $playerName)),
                    $main->getDatabaseManager()->getSeasonsRepository()->loadPlayer($playerUuid, $currentSeason->id),
                ]);

                $this->kingdom      = $main->getKingdomManager()->getKingdomById($playerData->kingdom);
                $this->abilities    = array_filter($playerData->abilities, fn(string $abilityId) => $main->getAbilityManager()->getAbilityById($abilityId) !== null);
                $this->kills        = $playerData->kills;
                $this->deaths       = $playerData->deaths;
                $this->seasonPlayer = $seasonData;

                if ($playerData->kingdom !== null){
                    $playerLoyalty = yield from $main->getDatabaseManager()->getPlayerLoyaltyRepository()->getLoyalty(
                        new UuidPayload($playerUuid), new InsertPlayerLoyaltyPayload($playerUuid, $playerName, $playerData->kingdom)
                    );
                    $this->loyalty = $playerLoyalty;
                }

                $this->player->setNoClientPredictions(false);
                $this->loaded = true;
                $main->getLogger()->debug(TextFormat::GREEN . "{$this->player->getName()} data's has been loaded successfully.");

                PlayerJoinHandler::handle($this->player);

                $main->getScheduler()->scheduleRepeatingTask(new SessionTask($this), 20);

                if ($this->kingdom === null) {
                    $this->promptKingdomChoice();
                }
            } catch (Throwable $e) {
                Utils::onFailure($e, $this->player, "Failed to load data for player " . $this->player->getName());
                $this->player->kick("§cAn error occurred while loading your data. Please try again later.");
                return;
            }
        });
    }

    private function promptKingdomChoice(): void {
        (new ChooseKingdomInventory())->send($this->player);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function getKingdom(): ?Kingdom
    {
        return $this->kingdom;
    }

    public function setKingdom(?Kingdom $kingdom): void
    {
        $this->kingdom = $kingdom;
    }

    public function isChoosingKingdom(): bool
    {
        return $this->choosingKingdom;
    }

    public function setChoosingKingdom(bool $choosingKingdom): void
    {
        $this->choosingKingdom = $choosingKingdom;
    }

    public function getAbilities(): array
    {
        return $this->abilities;
    }

    public function addAbilities(array $abilities, bool $update = false): void
    {
        foreach ($abilities as $ability){
            $this->abilities[] = $ability;
        }

        if ($update){
            Main::getInstance()->getDatabaseManager()
                ->getPlayerRepository()
                ->updatePlayerAbilities(new UpdatePlayerAbilities(
                    uuid: $this->player->getUniqueId()->toString(),
                    abilities: $this->abilities
                ));
        }
    }

    /**
     * @return int
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * @return int
     */
    public function getDeaths(): int
    {
        return $this->deaths;
    }

    public function addKill(): void
    {
        $this->kills++;
        $this->seasonPlayer?->incrementKills();
        $this->dirty = true;
    }

    public function addDeath(): void
    {
        $this->deaths++;
        $this->seasonPlayer?->incrementDeaths();
        $this->dirty = true;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function setFrozen(bool $newState): void
    {
        $this->frozen = $newState;
    }

    public function hasKingdom(): bool
    {
        return $this->kingdom !== null;
    }

    public function flushStats(): Generator
    {
        if ($this->dirty) {
            yield from Main::getInstance()->getDatabaseManager()->getPlayerRepository()->updateStats(new UpdatePlayerStatsPayload(
                uuid: $this->player->getUniqueId()->toString(),
                kills: $this->kills,
                deaths: $this->deaths
            ));

            $this->dirty = false;
            return true;
        }

        return false;
    }

    public function getSeasonPlayer(): ?SeasonPlayer
    {
        return $this->seasonPlayer;
    }

    public function getLoyalty(): ?PlayerLoyalty
    {
        return $this->loyalty;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setDirty(): void
    {
        $this->dirty = true;
    }

}

