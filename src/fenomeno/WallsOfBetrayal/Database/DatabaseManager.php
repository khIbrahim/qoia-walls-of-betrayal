<?php
namespace fenomeno\WallsOfBetrayal\Database;

use fenomeno\WallsOfBetrayal\Database\BinaryParser\MySQLBinaryStringParser;
use fenomeno\WallsOfBetrayal\Database\Contrasts\BinaryStringParserInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\BountyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\CooldownRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\EconomyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\FloatingTextRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KingdomRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KitRequirementRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\NpcRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerInventoriesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRolesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PunishmentRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\SeasonsRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\VaultRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Repository\CooldownRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\EconomyRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\FloatingTextRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KingdomBountyRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KingdomRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KingdomVoteRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KitRequirementRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\NpcRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerInventoriesRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerRolesRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\BanRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\MuteRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\ReportRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\SeasonsRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\VaultRepository;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\DataConnector;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\libasynql;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use Throwable;

/**
 * @mixin DataConnector
 */
class DatabaseManager
{

    private const DEFAULT_DATABASE_TYPE = "mysql";

    private DataConnector $database;
    private SqlQueriesFileManager $queriesFileManager;

    private PlayerRepositoryInterface $playerRepository;
    private KitRequirementRepositoryInterface $kitRequirementRepository;
    private CooldownRepositoryInterface $cooldownRepository;
    private EconomyRepositoryInterface $economyRepository;
    private PlayerRolesRepositoryInterface $rolesRepository;
    private VaultRepositoryInterface $vaultRepository;
    private KingdomRepositoryInterface $kingdomRepository;
    private PunishmentRepositoryInterface $muteRepository;
    private PunishmentRepositoryInterface $banRepository;
    private PunishmentRepositoryInterface $reportRepository;
    private FloatingTextRepositoryInterface $floatingTextRepository;
    private NpcRepositoryInterface $npcRepository;
    private BountyRepositoryInterface $bountyRepository;
    private KingdomVoteRepository $kingdomVoteRepository;
    private PlayerInventoriesRepositoryInterface $playerInventoriesRepository;
    private SeasonsRepositoryInterface $seasonsRepository;

    private BinaryStringParserInterface $binaryStringParser;
    private BigEndianNbtSerializer $nbtSerializer;

    public function __construct(
        private readonly Main $main
    ){
        try {
            $config = $this->main->getConfig()->get("database", []);
            $type   = $config["type"] ?? self::DEFAULT_DATABASE_TYPE;

            $repositories = [
                PlayerRepository::class,
                KitRequirementRepository::class,
                CooldownRepository::class,
                EconomyRepository::class,
                PlayerRolesRepository::class,
                VaultRepository::class,
                KingdomRepository::class,
                MuteRepository::class,
                BanRepository::class,
                ReportRepository::class,
                FloatingTextRepository::class,
                NpcRepository::class,
                KingdomBountyRepository::class,
                KingdomVoteRepository::class,
                PlayerInventoriesRepository::class,
                SeasonsRepository::class,
            ];

            $this->queriesFileManager = new SqlQueriesFileManager($type, $repositories);
            $this->queriesFileManager->addSharedFile(SqlQueriesFileManager::MYSQL, 'queries/mysql/punishment_history.sql');

            $this->database = libasynql::create($this->main, $config, $this->queriesFileManager->getAllQueryFiles());

            $this->binaryStringParser = new MySQLBinaryStringParser();

            $this->playerRepository = new PlayerRepository($this->main);
            $this->playerRepository->init($this);

            $this->kitRequirementRepository = new KitRequirementRepository($this->main);
            $this->kitRequirementRepository->init($this);

            $this->cooldownRepository = new CooldownRepository($this->main);
            $this->cooldownRepository->init($this);

            $this->economyRepository = new EconomyRepository($this->main);
            $this->economyRepository->init($this);

            $this->rolesRepository = new PlayerRolesRepository($this->main);
            $this->rolesRepository->init($this);

            $this->vaultRepository = new VaultRepository($this->main);
            $this->vaultRepository->init($this);

            $this->kingdomRepository = new KingdomRepository($this->main);
            $this->kingdomRepository->init($this);

            $this->muteRepository = new MuteRepository($this->main);
            $this->muteRepository->init($this);

            $this->banRepository = new BanRepository($this->main);
            $this->banRepository->init($this);

            $this->reportRepository = new ReportRepository($this->main);
            $this->reportRepository->init($this);

            $this->floatingTextRepository = new FloatingTextRepository($this->main);
            $this->floatingTextRepository->init($this);

            $this->npcRepository = new NpcRepository($this->main);
            $this->npcRepository->init($this);

            $this->bountyRepository = new KingdomBountyRepository($this->main);
            $this->bountyRepository->init($this);

            $this->kingdomVoteRepository = new KingdomVoteRepository($this->main);
            $this->kingdomVoteRepository->init($this);

            $this->playerInventoriesRepository = new PlayerInventoriesRepository($this->main);
            $this->playerInventoriesRepository->init($this);

            $this->seasonsRepository = new SeasonsRepository($this->main);
            $this->seasonsRepository->init($this);

            $this->nbtSerializer = new BigEndianNbtSerializer();
        } catch (Throwable $e){
            $this->main->getLogger()->error("§cAn error occurred while init database: " . $e->getMessage());
            $this->main->getLogger()->logException($e);
        }
    }

    public function getPlayerRepository(): PlayerRepositoryInterface
    {
        return $this->playerRepository;
    }

    public function getKitRequirementRepository(): KitRequirementRepositoryInterface
    {
        return $this->kitRequirementRepository;
    }

    public function getCooldownRepository(): CooldownRepositoryInterface
    {
        return $this->cooldownRepository;
    }

    public function getEconomyRepository(): EconomyRepositoryInterface
    {
        return $this->economyRepository;
    }

    public function getRolesRepository(): PlayerRolesRepositoryInterface
    {
        return $this->rolesRepository;
    }

    public function getVaultRepository(): VaultRepositoryInterface
    {
        return $this->vaultRepository;
    }

    public function getKingdomRepository(): KingdomRepositoryInterface
    {
        return $this->kingdomRepository;
    }

    public function getMuteRepository(): PunishmentRepositoryInterface
    {
        return $this->muteRepository;
    }

    public function getBanRepository(): PunishmentRepositoryInterface
    {
        return $this->banRepository;
    }

    public function getReportRepository(): PunishmentRepositoryInterface
    {
        return $this->reportRepository;
    }

    public function getFloatingTextRepository(): FloatingTextRepositoryInterface
    {
        return $this->floatingTextRepository;
    }

    public function getNpcRepository(): NpcRepositoryInterface
    {
        return $this->npcRepository;
    }

    public function getBountyRepository(): BountyRepositoryInterface
    {
        return $this->bountyRepository;
    }

    public function getKingdomVoteRepository(): KingdomVoteRepository
    {
        return $this->kingdomVoteRepository;
    }

    public function getBinaryStringParser(): BinaryStringParserInterface
    {
        return $this->binaryStringParser;
    }

    public function getPlayerInventoriesRepository(): PlayerInventoriesRepositoryInterface
    {
        return $this->playerInventoriesRepository;
    }

    public function getSeasonsRepository(): SeasonsRepositoryInterface
    {
        return $this->seasonsRepository;
    }

    public function readItems(?string $data, string $tagInventory) : array{
        if ($data === "" || $data === null) {
            return [];
        }

        $contents = [];
        $inventoryTag = $this->nbtSerializer->read(zlib_decode($data))->mustGetCompoundTag()->getListTag($tagInventory);
        /** @var CompoundTag $tag */
        foreach($inventoryTag as $tag){
            $contents[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
        }

        return $contents;
    }

    public function writeItems(array $c, string $tagInventory) : string{
        $contents = [];
        foreach($c as $slot => $item){
            $contents[] = $item->nbtSerialize($slot);
        }

        return zlib_encode($this->nbtSerializer->write(new TreeRoot(CompoundTag::create()
            ->setTag($tagInventory, new ListTag($contents, NBT::TAG_Compound))
        )), ZLIB_ENCODING_GZIP);
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->database, $name], $arguments);
    }

}