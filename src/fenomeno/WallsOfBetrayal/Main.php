<?php
namespace fenomeno\WallsOfBetrayal;

use fenomeno\WallsOfBetrayal\Commands\Player\ChooseCommand;
use fenomeno\WallsOfBetrayal\Commands\Player\KitCommand;
use fenomeno\WallsOfBetrayal\Game\Kingdom\KingdomManager;
use fenomeno\WallsOfBetrayal\Game\Kit\KitsManager;
use fenomeno\WallsOfBetrayal\Game\Phase\PhaseManager;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenuHandler;
use fenomeno\WallsOfBetrayal\Listeners\KingdomListener;
use fenomeno\WallsOfBetrayal\Listeners\ScoreboardUpdateListener;
use fenomeno\WallsOfBetrayal\Utils\WobConfig;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Sessions\SessionListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    private KingdomManager  $kingdomManager;
    private DatabaseManager $databaseManager;
    private PhaseManager    $phaseManager;
    private KitsManager     $kitsManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();
        WobConfig::init($this);
        MessagesUtils::init($this);
    }

    protected function onEnable(): void
    {
        if(! InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        $this->kingdomManager  = new KingdomManager($this);
        $this->databaseManager = new DatabaseManager($this);
        $this->phaseManager    = new PhaseManager($this);
        $this->kitsManager     = new KitsManager($this);

        $this->getServer()->getCommandMap()->registerAll('wob', [
            new ChooseCommand($this),
            new KitCommand($this)
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new SessionListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new KingdomListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ScoreboardUpdateListener($this), $this);
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

    protected function onDisable(): void
    {
        $this->phaseManager->save();
    }

}