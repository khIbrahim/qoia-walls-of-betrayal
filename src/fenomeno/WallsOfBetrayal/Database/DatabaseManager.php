<?php
namespace fenomeno\WallsOfBetrayal\Database;

use fenomeno\WallsOfBetrayal\Database\BinaryParser\MySQLBinaryStringParser;
use fenomeno\WallsOfBetrayal\Database\Contrasts\BinaryStringParserInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\CooldownRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\EconomyRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KingdomRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\KitRequirementRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PlayerRolesRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\PunishmentRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Repository\VaultRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Repository\CooldownRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\EconomyRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KingdomRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\KitRequirementRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerRolesRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\BanRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\MuteRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\Punishment\ReportRepository;
use fenomeno\WallsOfBetrayal\Database\Repository\VaultRepository;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\DataConnector;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\libasynql;
use fenomeno\WallsOfBetrayal\Main;
use Throwable;

/***
 * @mixin DataConnector
 */
class DatabaseManager
{

    private DataConnector $database;

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

    private BinaryStringParserInterface $binaryStringParser;

    public function __construct(
        private readonly Main $main
    ){
        try {
            $this->database = libasynql::create($this->main, $this->main->getConfig()->get("database"), [
                "sqlite" => "queries/sqlite.sql",
                "mysql"  => "queries/mysql.sql"
            ]);

            // je garde en mysql pour le moment, je ferai plus tard une vérification du type la base de données
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

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->database, $name], $arguments);
    }

    public function getBinaryStringParser(): BinaryStringParserInterface
    {
        return $this->binaryStringParser;
    }

}