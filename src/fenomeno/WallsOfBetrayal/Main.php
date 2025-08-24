<?php
namespace fenomeno\WallsOfBetrayal;

use Exception;
use fenomeno\WallsOfBetrayal\Blocks\BlockManager;
use fenomeno\WallsOfBetrayal\Commands\Admin\FloatingTextCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\GiveKitCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\NpcCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\PortalCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\SetLobbyCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\SetLobbySettingCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\SetSpawnCommand;
use fenomeno\WallsOfBetrayal\Commands\Admin\SpawnerCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\AddBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\RemoveBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\SetBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\BalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\PayCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\RichCommand;
use fenomeno\WallsOfBetrayal\Commands\KingdomCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\AbilitiesCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\ChooseCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\CraftCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\FeedCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\KitCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\LobbyCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\NickCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\SellCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\ShopCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\SpawnCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\StatsCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\VaultCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Ban\BanCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Ban\BanListCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Ban\TempBanCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Ban\UnBanCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\HistoryCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Mute\MuteCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Mute\MuteListCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Mute\UnMuteCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Report\ReportCommand;
use fenomeno\WallsOfBetrayal\Commands\Punishment\Report\ReportsCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\CreateRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\ListRolesCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\Permission\AddPermissionCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\Permission\RemovePermissionCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\PlayerRoleInfoCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SetRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SubRole\AddSubRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SubRole\RemoveSubRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\InvseeCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\KickCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\RandomTpCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\StaffChatCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\StaffModCommand;
use fenomeno\WallsOfBetrayal\Commands\Staff\VanishCommand;
use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Economy\EconomyManager;
use fenomeno\WallsOfBetrayal\Entities\EntityManager;
use fenomeno\WallsOfBetrayal\Events\Staff\FreezeCommand;
use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityManager;
use fenomeno\WallsOfBetrayal\Game\Kingdom\KingdomManager;
use fenomeno\WallsOfBetrayal\Game\Kit\KitsManager;
use fenomeno\WallsOfBetrayal\Game\Phase\PhaseManager;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\PacketHooker;
use fenomeno\WallsOfBetrayal\libs\fenomeno\libWebhook\thread\DiscordWebhook;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenuHandler;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Listeners\AbilitiesListener;
use fenomeno\WallsOfBetrayal\Listeners\BlocksListener;
use fenomeno\WallsOfBetrayal\Listeners\EconomyListener;
use fenomeno\WallsOfBetrayal\Listeners\EntitiesListener;
use fenomeno\WallsOfBetrayal\Listeners\FloatingTextListener;
use fenomeno\WallsOfBetrayal\Listeners\KingdomListener;
use fenomeno\WallsOfBetrayal\Listeners\KitsListener;
use fenomeno\WallsOfBetrayal\Listeners\LobbyListener;
use fenomeno\WallsOfBetrayal\Listeners\NpcListener;
use fenomeno\WallsOfBetrayal\Listeners\PunishmentListener;
use fenomeno\WallsOfBetrayal\Listeners\RolesListener;
use fenomeno\WallsOfBetrayal\Listeners\ScoreboardUpdateListener;
use fenomeno\WallsOfBetrayal\Listeners\StaffListener;
use fenomeno\WallsOfBetrayal\Manager\CooldownManager;
use fenomeno\WallsOfBetrayal\Manager\FloatingTextManager;
use fenomeno\WallsOfBetrayal\Manager\NpcManager;
use fenomeno\WallsOfBetrayal\Manager\PunishmentManager;
use fenomeno\WallsOfBetrayal\Manager\ServerManager;
use fenomeno\WallsOfBetrayal\Manager\ShopManager;
use fenomeno\WallsOfBetrayal\Roles\RolesManager;
use fenomeno\WallsOfBetrayal\Services\NickService;
use fenomeno\WallsOfBetrayal\Sessions\SessionListener;
use fenomeno\WallsOfBetrayal\Tiles\TileManager;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use Generator;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use Throwable;

class Main extends PluginBase
{
    use SingletonTrait;

    private KingdomManager      $kingdomManager;
    private DatabaseManager     $databaseManager;
    private PhaseManager        $phaseManager;
    private KitsManager         $kitsManager;
    private AbilityManager      $abilityManager;
    private ShopManager         $shopManager;
    private CooldownManager     $cooldownManager;
    private EconomyManager      $economyManager;
    private RolesManager        $rolesManager;
    private PunishmentManager   $punishmentManager;
    private NpcManager          $npcManager;
    private FloatingTextManager $floatingTextManager;
    private ServerManager       $serverManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();
        WobConfig::init($this);
        MessagesUtils::init($this);
    }

    protected function onEnable(): void
    {
        try {
            if(! InvMenuHandler::isRegistered()){
                InvMenuHandler::register($this);
            }

            if(! PacketHooker::isRegistered()){
                PacketHooker::register($this);
            }

            if(! DiscordWebhook::isRegistered()){
                DiscordWebhook::init($this);
            }

            $this->databaseManager     = new DatabaseManager($this);
            $this->serverManager       = new ServerManager($this);
            $this->abilityManager      = new AbilityManager($this);
            $this->kingdomManager      = new KingdomManager($this);
            $this->phaseManager        = new PhaseManager($this);
            $this->kitsManager         = new KitsManager($this);
            $this->shopManager         = new ShopManager($this);
            $this->cooldownManager     = new CooldownManager($this);
            $this->economyManager      = new EconomyManager($this);
            $this->rolesManager        = new RolesManager($this);
            $this->punishmentManager   = new PunishmentManager($this);
            $this->npcManager          = new NpcManager($this);
            $this->floatingTextManager = new FloatingTextManager($this);

            EntityManager::getInstance()->startup($this);
            TileManager::getInstance()->startup();
            BlockManager::getInstance()->startup();

            $this->getServer()->getCommandMap()->registerAll('wob', [
                new ChooseCommand($this),
                new KitCommand($this),
                new AbilitiesCommand($this),
                new ShopCommand($this),
                new BalanceCommand($this),
                new PayCommand($this),
                new RichCommand($this),
                new AddBalanceCommand($this),
                new SetBalanceCommand($this),
                new RemoveBalanceCommand($this),
                new FeedCommand($this),
                new SetRoleCommand($this),
                new AddPermissionCommand($this),
                new RemovePermissionCommand($this),
                new PlayerRoleInfoCommand($this),
                new AddSubRoleCommand($this),
                new RemoveSubRoleCommand($this),
                new CreateRoleCommand($this),
                new ListRolesCommand($this),
                new VaultCommand($this),
                new GiveKitCommand($this),
                new SpawnerCommand($this),
                new SellCommand($this),
                new StatsCommand($this),
                new CraftCommand($this),
                new NickCommand($this),
                new MuteCommand($this),
                new UnMuteCommand($this),
                new MuteListCommand($this),
                new BanCommand($this),
                new UnBanCommand($this),
                new TempBanCommand($this),
                new BanListCommand($this),
                new ReportCommand($this),
                new ReportsCommand($this),
                new KickCommand($this),
                new StaffChatCommand($this),
                new StaffModCommand($this),
                new VanishCommand($this),
                new FreezeCommand($this),
                new HistoryCommand($this),
                new RandomTpCommand($this),
                new InvseeCommand($this),
                new NpcCommand($this),
                new FloatingTextCommand($this),
                new LobbyCommand($this),
                new SetLobbyCommand($this),
                new SpawnCommand($this),
                new SetSpawnCommand($this),
                new KingdomCommand($this),
                new PortalCommand($this),
                new SetLobbySettingCommand($this)
            ]);

            $this->getServer()->getPluginManager()->registerEvents(new SessionListener(), $this);
            $this->getServer()->getPluginManager()->registerEvents(new KingdomListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new ScoreboardUpdateListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new KitsListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new AbilitiesListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new EconomyListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new RolesListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new EntitiesListener(), $this);
            $this->getServer()->getPluginManager()->registerEvents(new BlocksListener(), $this);
            $this->getServer()->getPluginManager()->registerEvents(new PunishmentListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new StaffListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new NpcListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new FloatingTextListener($this), $this);
            $this->getServer()->getPluginManager()->registerEvents(new LobbyListener($this), $this);

            Await::g2c(
                $this->loadDependencies(),
                function(){
                    $this->getLogger()->info(TextFormat::GREEN . "Dependencies successfully loaded");
                    try {
                        NickService::init($this);
                    } catch (Throwable){}
                },
                function (Throwable $e){
                    $this->getLogger()->error(TextFormat::RED . "Failed to load dependencies: " . $e->getMessage());
                    $this->getLogger()->logException($e);
                }
            );

            $this->getLogger()->info(TextFormat::GREEN . "WallsOfBetrayal plugin enabled successfully");
        } catch (Throwable $e){
            $this->getLogger()->error(TextFormat::RED . "An error occurred while enabling WallsOfBetrayal: " . $e->getMessage());
            $this->getLogger()->logException($e);
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function getDatabaseManager(): DatabaseManager
    {
        return $this->databaseManager;
    }

    public function getKingdomManager(): KingdomManager
    {
        return $this->kingdomManager;
    }

    public function getPhaseManager(): PhaseManager
    {
        return $this->phaseManager;
    }

    public function getKitsManager(): KitsManager
    {
        return $this->kitsManager;
    }

    public function getAbilityManager(): AbilityManager
    {
        return $this->abilityManager;
    }

    public function getShopManager(): ShopManager
    {
        return $this->shopManager;
    }

    public function getCooldownManager(): CooldownManager
    {
        return $this->cooldownManager;
    }

    public function getEconomyManager(): EconomyManager
    {
        return $this->economyManager;
    }

    public function getRolesManager(): RolesManager
    {
        return $this->rolesManager;
    }

    public function getPunishmentManager(): PunishmentManager
    {
        return $this->punishmentManager;
    }

    public function getNpcManager(): NpcManager
    {
        return $this->npcManager;
    }

    public function getFloatingTextManager(): FloatingTextManager
    {
        return $this->floatingTextManager;
    }

    public function getServerManager(): ServerManager
    {
        return $this->serverManager;
    }

    protected function onDisable(): void
    {
        $this->phaseManager->save();
        $this->economyManager->clearCache();

        $this->databaseManager->waitAll();
        $this->databaseManager->close();

        $this->floatingTextManager->cleanup();
    }

    public function loadDependencies(): Generator
    {
        return Await::promise(function($resolve, $reject){
            try {
                $autoloadPath = __DIR__ . "/../../../vendor/autoload.php";
                if (file_exists($autoloadPath)) {

                    require_once $autoloadPath;
                    $resolve();
                } else {
                    $reject(new Exception("Autoload file not found at $autoloadPath"));
                }
            } catch (Throwable $e){
                $reject($e);
            }
        });
    }

    public function getFile(): string{
        return parent::getFile();
    }

}