<?php
namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\Class\KingdomData;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\DTO\KingdomEnchantment;
use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\Sound;
use Throwable;

class Kingdom
{

    private KingdomData $kingdomData;

    public function __construct(
        public string $id,
        public string $displayName,
        public string $color,
        public string $description,
        public ?Item $item = null,
        public ?Position $spawn = null,
        public array $kits = [],
        public array $abilities = [],
        public string $portalId = "",
        public array $enchantments = [],
    ){}

    public function broadcastMessage(string $message, array $extraTags = [], ?string $default = null): void
    {
        foreach ($this->getOnlineMembers() as $member){
            MessagesUtils::sendTo($member, $message, $extraTags, $default);
        }
    }

    public function broadcastSound(Sound $sound): void
    {
        foreach ($this->getOnlineMembers() as $player){
            $player->broadcastSound($sound);
        }
    }

    /** @return Player[] */
    public function getOnlineMembers(): array
    {
        $members = [];

        foreach (Server::getInstance()->getOnlinePlayers() as $player){
            $session = Session::get($player);
            if ($session->isLoaded() && $session->getKingdom() !== null && $session->getKingdom()->id === $this->id){
                $members[] = $player;
            }
        }

        return $members;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /** @return Kit[] */
    public function getKits(): array
    {
        return $this->kits;
    }

    public function getKingdomData(): KingdomData
    {
        return $this->kingdomData;
    }

    public function setKingdomData(KingdomData $kingdomData): void
    {
        $this->kingdomData = $kingdomData;
    }

    /** @return KingdomEnchantment[] */
    public function getEnchantments(): array
    {
        return $this->enchantments;
    }

    /**
     * TODO
     *
     * @param Player $player
     * @param KingdomEnchantment $enchantment
     * @return void
     */
    public function applyEnchantmentToPlayer(Player $player, KingdomEnchantment $enchantment): void
    {
        $item = clone $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            MessagesUtils::sendTo($player, "You must hold an item to enchant it.");
            return;
        }

        $enchantmentInstance = $enchantment->getEnchantmentInstance();
        $item->addEnchantment($enchantmentInstance);

        $player->getInventory()->setItemInHand($item);
        $player->sendMessage("TODO");
    }

    public function addDeath(): void
    {
        Await::g2c(
            Main::getInstance()->getDatabaseManager()->getKingdomRepository()->addDeath(new IdPayload($this->id)),
            fn() => $this->kingdomData->deaths++,
            fn(Throwable $e) => Main::getInstance()->getLogger()->error("Failed to add death for kingdom $this->id: " . $e->getMessage())
        );
    }

    public function addKill(): void
    {
        Await::g2c(
            Main::getInstance()->getDatabaseManager()->getKingdomRepository()->addKill(new IdPayload($this->id)),
            fn() => $this->kingdomData->deaths++,
            fn(Throwable $e) => Main::getInstance()->getLogger()->error("Failed to add kill for kingdom $this->id: " . $e->getMessage())
        );
    }

}