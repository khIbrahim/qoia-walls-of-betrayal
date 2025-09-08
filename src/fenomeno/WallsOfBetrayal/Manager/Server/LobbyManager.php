<?php

namespace fenomeno\WallsOfBetrayal\Manager\Server;

use fenomeno\WallsOfBetrayal\Config\PermissionIds;
use fenomeno\WallsOfBetrayal\Events\PlayerJoinLobbyEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\PositionParser;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\entity\Location;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\EndermanTeleportSound;
use Symfony\Component\Filesystem\Path;
use Throwable;

class LobbyManager
{

    public const PVP         = 'pvp';
    public const BUILD       = 'build';
    public const BREAK       = 'break';
    public const DROP        = 'drop';
    public const PICKUP      = 'pickup';
    public const HUNGER      = 'hunger';
    public const DAMAGE      = 'damage';
    public const INTERACT    = 'interact';
    public const VOID_TP     = 'void_tp';
    public const DOUBLE_JUMP = 'double_jump';
    public const CLEAR       = 'clear_inventory_on_join';
    public const GIVE        = 'give_hotbar';

    private const LOBBY_FILE    = 'lobby.yml';
    private const SETTINGS_KEY  = 'settings';
    private const LOBBY_LOC_KEY = 'lobbyLoc';

    private Config $config;
    private null|Location|Position $lobbyLoc;
    private array $settings;
    private array $items;

    public function __construct(private readonly Main $main){
        $this->loadLobbyLoc();

        $this->main->saveResource(self::LOBBY_FILE);
        $lobbyFilePath = Path::join($this->main->getDataFolder() . self::LOBBY_FILE);
        $this->config  = new Config($lobbyFilePath, Config::YAML);

        $this->settings = $this->config->get(self::SETTINGS_KEY, [
            self::PVP         => false,
            self::BUILD       => false,
            self::BREAK       => false,
            self::DROP        => false,
            self::PICKUP      => false,
            self::HUNGER      => false,
            self::DAMAGE      => false,
            self::INTERACT    => false,
            self::VOID_TP     => true,
            self::DOUBLE_JUMP => true,
            self::CLEAR       => true,
            self::GIVE        => true
        ]);

        $this->items = (Utils::loadItems($this->config->get("hotbar"))[0]) ?? [];
    }

    private function loadLobbyLoc(): void
    {
        try {
            $this->lobbyLoc = PositionParser::load($this->config->get(self::LOBBY_LOC_KEY));
        } catch (Throwable){
            $this->lobbyLoc = $this->main->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
        }
    }

    public function updateLobbyLoc(Location $location): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($location) {
            try {
                $this->lobbyLoc = $location;
                $this->config->set(self::LOBBY_LOC_KEY, PositionParser::toArray($location));
                $this->config->save();
                $resolve($location);
            } catch (Throwable $e) {$reject($e);}
        });
    }

    public function getLobbyLoc(): Position|Location
    {
        return $this->lobbyLoc;
    }

    public function getSetting(string $key, mixed $default = false): bool
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSettingByPlayer(string $key, ?Player $player = null): bool
    {
        $val = $this->getSetting($key);
        if ($player && $this->canBypass($player, $key)){
            $val = true;
        }

        return $val;
    }

    /** @throws */
    public function setSetting(string $key, bool $val): void
    {
        $this->settings[$key] = $val;
        $this->config->setNested(self::SETTINGS_KEY . '.' . $key, $val);
        $this->config->save();
    }

    /** @throws */
    public function save(): void
    {
        $this->config->set(self::SETTINGS_KEY, $this->settings);
        $this->config->save();
    }

    public function isInLobby(Player $player): bool
    {
        return $this->lobbyLoc !== null && strtolower($player->getWorld()->getFolderName()) === strtolower($this->lobbyLoc->getWorld()->getFolderName());
    }

    public function teleport(Player $player): void
    {
        $ev = new PlayerJoinLobbyEvent($player, $this->getLobbyLoc());
        $ev->call();
        if($ev->isCancelled()){
            return;
        }

        $pos = $ev->getLocation();
        $player->teleport($pos);
        $player->broadcastSound(new EndermanTeleportSound());
        $player->getWorld()->addParticle($pos, new EndermanTeleportParticle());

        if($this->getSetting(self::CLEAR, true)){
            $player->setGamemode(GameMode::SURVIVAL);
            $player->setFlying(false);
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getOffHandInventory()->clearAll();
            $player->setHealth($player->getMaxHealth());
            $player->getHungerManager()->setSaturation(20.0);
            $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
        }

        if (isset($this->items) && $this->getSetting(self::GIVE, true)){
            $player->getInventory()->setContents($this->items);
        }
    }

    public function canBypass(Player $player, string $key): bool
    {
        return $this->isInLobby($player) && ($player->hasPermission(PermissionIds::BYPASS_LOBBY) || $player->hasPermission(PermissionIds::getLobbyPerm($key)));
    }

}