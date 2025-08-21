<?php

namespace fenomeno\WallsOfBetrayal\Sessions;

use fenomeno\WallsOfBetrayal\Config\StaffConfig;
use fenomeno\WallsOfBetrayal\DTO\StaffSessionDTO;
use fenomeno\WallsOfBetrayal\Events\Staff\PlayerEnterStaffChatEvent;
use fenomeno\WallsOfBetrayal\Events\Staff\PlayerEnterStaffModEvent;
use fenomeno\WallsOfBetrayal\Events\Staff\PlayerLeaveStaffChatEvent;
use fenomeno\WallsOfBetrayal\Events\Staff\PlayerLeaveStaffModEvent;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Services\NotificationService;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use Throwable;
use WeakMap;

class StaffSession
{

    private static WeakMap $map;

    private bool $inStaffMode = false;
    private bool $vanish = false;

    private ?StaffSessionDTO $cache = null;
    private bool $staffChat = false;

    private static Config $config;

    public static function get(Player $player): StaffSession
    {
        if (! isset(self::$map)) {
            self::$map = new WeakMap();
        }
        if(! isset(self::$config)) {
            self::$config = new Config(StaffConfig::getConfigPath(), Config::JSON);
            self::$config->enableJsonOption(JSON_INVALID_UTF8_SUBSTITUTE);
            self::$config->enableJsonOption(JSON_UNESCAPED_UNICODE);

        }
        return self::$map[$player] ??= new self($player);
    }

    private function __construct(
        private readonly Player $player,
    ) {}

    public function isInStaffMode(): bool
    {
        return $this->inStaffMode;
    }

    public function isVanished(): bool {
        return $this->vanish;
    }

    public function setInStaffMode(bool $inStaffMode): void
    {
        try {
            if ($inStaffMode) {
                $ev = new PlayerEnterStaffModEvent($this->player);
                $ev->call();
                if ($ev->isCancelled()) {
                    return;
                }

                $this->cache = $cache = new StaffSessionDTO(
                    inventory: $this->player->getInventory()->getContents(),
                    offhandInventory: $this->player->getOffHandInventory()->getContents(),
                    armorInventory: $this->player->getArmorInventory()->getContents(),
                    xp: $this->player->getXpManager()->getXpLevel(),
                    xpProgress: $this->player->getXpManager()->getXpProgress(),
                    allowFlight: $this->player->getAllowFlight(),
                    gameMode: $this->player->getGamemode()
                );

                self::$config->set($this->player->getName(), $cache->jsonSerialize());
                self::$config->save();

                $this->setVanish(true);

                $this->player->getInventory()->clearAll();
                $this->player->getOffHandInventory()->clearAll();
                $this->player->getArmorInventory()->clearAll();
                $this->player->getXpManager()->setXpAndProgress(0, 0);

                $this->player->getInventory()->setContents(StaffConfig::getStaffInventory());
                $this->player->setScoreTag("§r§cStaff Mod§r");

                MessagesUtils::sendTo($this->player, MessagesIds::ENTER_STAFF_MOD);
            } else {
                $ev = new PlayerLeaveStaffModEvent($this->player);
                $ev->call();

                $this->setVanish(false);
                if($this->cache === null){
                    return;
                }

                $c = $this->cache;

                $this->player->getInventory()->setContents($c->inventory);
                $this->player->getOffHandInventory()->setContents($c->offhandInventory);
                $this->player->getArmorInventory()->setContents($c->armorInventory);
                $this->player->getXpManager()->setXpLevel($c->xp);
                $this->player->getXpManager()->setXpProgress($c->xpProgress);
                $this->player->setGamemode($c->gameMode);

                $this->player->setScoreTag("");
                $this->cache = null;

                if (self::$config->exists($this->player->getName())) {
                    self::$config->remove($this->player->getName());
                    self::$config->save();
                }

                MessagesUtils::sendTo($this->player, MessagesIds::LEAVE_STAFF_MOD);
            }
        } catch (Throwable $e) {
            MessagesUtils::sendTo($this->player, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            Main::getInstance()->getLogger()->error("Failed to toggle staffmod to " . $this->player->getName() . ": " . $e->getMessage());
            Main::getInstance()->getLogger()->logException($e);
        }

        $this->inStaffMode = $inStaffMode;
    }

    public function loadFromConfig(): void
    {
        if (! self::$config->exists($this->player->getName())){
            return;
        }

        $data = self::$config->get($this->player->getName());
        try {
            $this->cache = StaffSessionDTO::fromArray($data);

            $this->player->getInventory()->setContents($this->cache->inventory);
            $this->player->getOffHandInventory()->setContents($this->cache->offhandInventory);
            $this->player->getArmorInventory()->setContents($this->cache->armorInventory);
            $this->player->getXpManager()->setXpAndProgress($this->cache->xp, $this->cache->xpProgress);
            $this->player->setGamemode($this->cache->gameMode);
        } catch (Throwable $e) {
            MessagesUtils::sendTo($this->player, MessagesIds::ERROR, [ExtraTags::ERROR => $e->getMessage()]);
            Main::getInstance()->getLogger()->error("Failed to load staffmod to " . $this->player->getName() . ": " . $e->getMessage());
            Main::getInstance()->getLogger()->logException($e);
        }
    }

    public function setVanish(bool $vanish): void
    {
        $this->vanish = $vanish;
        $this->applyVanish($vanish);
    }

    public function isStaffChatEnabled(): bool
    {
        return $this->staffChat;
    }

    public function setStaffChat(bool $enabled): void
    {
        if ($enabled){
            $ev = new PlayerEnterStaffChatEvent($this->player);
            $ev->call();
            if(! $ev->isCancelled()){
                NotificationService::broadcastEnterStaffChat($this->player);
                $this->staffChat = true;
            }
        } else {
            $ev = new PlayerLeaveStaffChatEvent($this->player);
            $ev->call();
            if(! $ev->isCancelled()){
                NotificationService::broadcastLeaveStaffChat($this->player);
                $this->staffChat = false;
            }
        }
    }

    private function applyVanish(bool $vanish): void
    {
        if ($vanish){
            $this->player->setHasBlockCollision(false);
            $this->player->setAllowFlight(true);
        } else {
            $this->player->setHasBlockCollision(true);
            $this->player->setAllowFlight(false);
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if ($onlinePlayer === $this->player) continue;

            if ($vanish) {
                if (! $onlinePlayer->hasPermission("moderation.permission.vanish")){
                    $onlinePlayer->hidePlayer($this->player);
                    $onlinePlayer->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([
                        PlayerListEntry::createRemovalEntry($this->player->getUniqueId())
                    ]));
                }
            } else {
                $onlinePlayer->showPlayer($this->player);
                $onlinePlayer->getNetworkSession()->sendDataPacket(PlayerListPacket::add([
                    PlayerListEntry::createAdditionEntry(
                        $this->player->getUniqueId(),
                        $this->player->getId(),
                        $this->player->getDisplayName(),
                        $onlinePlayer->getNetworkSession()->getTypeConverter()->getSkinAdapter()->toSkinData($this->player->getSkin()),
                        $this->player->getXuid()
                    )
                ]));
            }
        }

        $this->player->setSkin($this->player->getSkin());
        $this->player->sendSkin();
    }

}