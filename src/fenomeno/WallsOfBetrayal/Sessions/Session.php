<?php
namespace fenomeno\WallsOfBetrayal\Sessions;

use fenomeno\WallsOfBetrayal\Database\Payload\Player\LoadPlayerPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Player\UpdatePlayerAbilities;
use fenomeno\WallsOfBetrayal\DTO\PlayerData;
use fenomeno\WallsOfBetrayal\Game\Kingdom\Kingdom;
use fenomeno\WallsOfBetrayal\Handlers\PlayerJoinHandler;
use fenomeno\WallsOfBetrayal\Inventory\ChooseKingdomInventory;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\MessagesUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WeakMap;

class Session
{

    private static WeakMap $map;
    private array $abilities = [];

    public static function get(Player $player) : Session
    {
        if (! isset(self::$map)){
            self::$map = new WeakMap();
        }

        return self::$map[$player] ??= new self($player);
    }

    private bool $loaded          = false;

    private ?Kingdom $kingdom     = null;
    private bool $choosingKingdom = false;

    public function __construct(private readonly Player $player){}

    public function load(): void
    {
        $this->player->setNoClientPredictions();
        $this->player->sendTitle("§7Loading your data...");

        $payload = new LoadPlayerPayload($this->player->getUniqueId()->toString());
        Main::getInstance()->getDatabaseManager()->getPlayerRepository()->load($payload)
            ->onCompletion(function (?PlayerData $data){
                if ($data !== null){
                    $this->kingdom   = Main::getInstance()->getKingdomManager()->getKingdomById($data->kingdom);
                    $this->abilities = array_filter($data->abilities, fn(string $abilityId) => Main::getInstance()->getAbilityManager()->getAbilityById($abilityId) !== null);
                }

                $this->player->setNoClientPredictions(false);
                $this->loaded = true;
                Main::getInstance()->getLogger()->info(TextFormat::GREEN . "{$this->player->getName()} data's has been loaded successfully.");

                if ($this->kingdom === null){
                    $this->promptKingdomChoice();
                }

                PlayerJoinHandler::handle($this->player);
            }, function (){
                $this->player->kick(MessagesUtils::getMessage('unstable'));
            });
    }

    private function promptKingdomChoice(): void {
        (new ChooseKingdomInventory())->send($this->player);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function getKingdom(): ?Kingdom
    {
        return $this->kingdom;
    }

    public function setKingdom(?Kingdom $kingdom): void
    {
        $this->kingdom = $kingdom;
    }

    public function isChoosingKingdom(): bool
    {
        return $this->choosingKingdom;
    }

    public function setChoosingKingdom(bool $choosingKingdom): void
    {
        $this->choosingKingdom = $choosingKingdom;
    }

    public function getAbilities(): array
    {
        return $this->abilities;
    }

    public function addAbilities(array $abilities, bool $update = false): void
    {
        foreach ($abilities as $ability){
            $this->abilities[] = $ability;
        }

        if ($update){
            Main::getInstance()->getDatabaseManager()
                ->getPlayerRepository()
                ->updatePlayerAbilities(new UpdatePlayerAbilities(
                    uuid: $this->player->getUniqueId()->toString(),
                    abilities: $this->abilities
                ));
        }
    }

}