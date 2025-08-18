<?php
namespace fenomeno\WallsOfBetrayal\Game\Kingdom;

use fenomeno\WallsOfBetrayal\DTO\KingdomEnchantment;
use fenomeno\WallsOfBetrayal\Game\Kit\Kit;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\Sound;

class Kingdom
{

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

}