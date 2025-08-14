<?php
namespace fenomeno\WallsOfBetrayal;

use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\AddBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\RemoveBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\Admin\SetBalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\BalanceCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\PayCommand;
use fenomeno\WallsOfBetrayal\Commands\Economy\RichCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\AbilitiesCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\ChooseCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\FeedCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\KitCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\ShopCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\CreateRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\ListRolesCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\Permission\AddPermissionCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\Permission\RemovePermissionCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\PlayerRoleInfoCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SetRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SubRole\AddSubRoleCommand;
use fenomeno\WallsOfBetrayal\Commands\Roles\SubRole\RemoveSubRoleCommand;
use fenomeno\WallsOfBetrayal\Config\WobConfig;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Economy\EconomyManager;
use fenomeno\WallsOfBetrayal\Game\Abilities\AbilityManager;
use fenomeno\WallsOfBetrayal\Game\Kingdom\KingdomManager;
use fenomeno\WallsOfBetrayal\Game\Kit\KitsManager;
use fenomeno\WallsOfBetrayal\Game\Phase\PhaseManager;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\exception\HookAlreadyRegistered;
use fenomeno\WallsOfBetrayal\libs\CortexPE\Commando\PacketHooker;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenuHandler;
use fenomeno\WallsOfBetrayal\Listeners\AbilitiesListener;
use fenomeno\WallsOfBetrayal\Listeners\EconomyListener;
use fenomeno\WallsOfBetrayal\Listeners\KingdomListener;
use fenomeno\WallsOfBetrayal\Listeners\KitsListener;
use fenomeno\WallsOfBetrayal\Listeners\RolesListener;
use fenomeno\WallsOfBetrayal\Listeners\ScoreboardUpdateListener;
use fenomeno\WallsOfBetrayal\Manager\CooldownManager;
use fenomeno\WallsOfBetrayal\Manager\ShopManager;
use fenomeno\WallsOfBetrayal\Roles\RolesManager;
use fenomeno\WallsOfBetrayal\Sessions\SessionListener;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    private KingdomManager  $kingdomManager;
    private DatabaseManager $databaseManager;
    private PhaseManager    $phaseManager;
    private KitsManager     $kitsManager;
    private AbilityManager  $abilityManager;
    private ShopManager     $shopManager;
    private CooldownManager $cooldownManager;
    private EconomyManager  $economyManager;
    private RolesManager    $rolesManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();
        WobConfig::init($this);
        MessagesUtils::init($this);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    protected function onEnable(): void
    {
        if(! InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        if(! PacketHooker::isRegistered()){
            PacketHooker::register($this);
        }

        $this->databaseManager = new DatabaseManager($this);
        $this->abilityManager  = new AbilityManager($this);
        $this->kingdomManager  = new KingdomManager($this);
        $this->phaseManager    = new PhaseManager($this);
        $this->kitsManager     = new KitsManager($this);
        $this->shopManager     = new ShopManager($this);
        $this->cooldownManager = new CooldownManager($this);
        $this->economyManager  = new EconomyManager($this);
        $this->rolesManager    = new RolesManager($this);

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
            new ListRolesCommand($this)
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new SessionListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new KingdomListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ScoreboardUpdateListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new KitsListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new AbilitiesListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EconomyListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new RolesListener($this), $this);
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

    protected function onDisable(): void
    {
        $this->phaseManager->save();
        $this->economyManager->clearCache();

        $this->databaseManager->waitAll();
        $this->databaseManager->close();
    }

}