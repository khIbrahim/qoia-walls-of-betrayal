<?php
namespace fenomeno\WallsOfBetrayal;

use fenomeno\WallsOfBetrayal\Commands\Player\ChooseCommand;
use fenomeno\WallsOfBetrayal\Game\Kingdom\KingdomManager;
use fenomeno\WallsOfBetrayal\libs\muqsit\invmenu\InvMenuHandler;
use fenomeno\WallsOfBetrayal\Listeners\KingdomListener;
use fenomeno\WallsOfBetrayal\Utils\KingdomConfig;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use fenomeno\WallsOfBetrayal\Database\DatabaseManager;
use fenomeno\WallsOfBetrayal\Sessions\SessionListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    private KingdomManager $kingdomManager;
    private DatabaseManager $databaseManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();
        KingdomConfig::init($this);
        MessagesUtils::init($this);
    }

    protected function onEnable(): void
    {
        if(! InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        $this->kingdomManager  = new KingdomManager($this);
        $this->databaseManager = new DatabaseManager($this);

        $this->getServer()->getCommandMap()->registerAll('wob', [
            new ChooseCommand($this)
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new SessionListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new KingdomListener(), $this);
    }

    public function getDatabaseManager(): DatabaseManager
    {
        return $this->databaseManager;
    }

    public function getKingdomManager(): KingdomManager
    {
        return $this->kingdomManager;
    }

}