<?php

namespace fenomeno\WallsOfBetrayal\Services;

use fenomeno\WallsOfBetrayal\Events\PlayerNickChangeEvent;
use fenomeno\WallsOfBetrayal\Events\PlayerNickResetEvent;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use GeminiAPI\Client;
use GeminiAPI\GenerationConfig;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use Generator;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use Throwable;

class NickService
{
    use SingletonTrait {
        reset as protected;
        setInstance as private;
    }

    private static array $nicks = [];
    private static Config $config;

    private const API_KEY = "AIzaSyCKDeMe7zFndYsHB7aV4MO02ULG2rziX0U";
    private const MODEL = ModelName::GEMINI_1_5_FLASH;
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 16;

    private ?Client $client = null;

    /** @throws */
    public static function init(Main $main) : void
    {
        self::setInstance(new self());

        $storagePath  = $main->getDataFolder() . "nicks.json";
        self::$config = new Config($storagePath, Config::JSON, []);

        $main->getServer()->getPluginManager()->registerEvent(
            event: PlayerJoinEvent::class,
            handler: function(PlayerJoinEvent $event): void {
                $player = $event->getPlayer();
                $playerName = $player->getName();

                if (self::hasNick($player)) {
                    $nick = self::getNick($playerName);
                    if ($nick !== null) {
                        $player->setDisplayName($nick);
                        MessagesUtils::sendTo($player, MessagesIds::NICK_REJOIN, [
                            ExtraTags::NICK => $nick
                        ]);
                    }
                }
            },
            priority: EventPriority::LOW,
            plugin: $main
        );

        $main->getServer()->getPluginManager()->registerEvent(
            event: DataPacketSendEvent::class,
            handler: function(DataPacketSendEvent $event) use ($main): void {
                $packets = $event->getPackets();

                foreach ($packets as $packet) {
                    if ($packet instanceof PlayerListPacket && $packet->type === PlayerListPacket::TYPE_ADD) {
                        foreach ($packet->entries as $entry) {
                            $player = $main->getServer()->getPlayerByUUID($entry->uuid);
                            if ($player instanceof Player) {
                                $playerName = $player->getName();
                                if (self::hasNick($player)) {
                                    $nickname = self::getNick($playerName);
                                    if ($nickname !== null) {
                                        $entry->username = $nickname;
                                    }
                                }
                            }
                        }
                    }
                }
            },
            priority: EventPriority::LOW,
            plugin: $main
        );
    }

    public function generate(): Generator
    {
        return Await::promise(closure: function ($resolve, $reject) {
            try {
                $prompt = $this->buildPrompt();
                $generationConfig = $this->buildGenerationConfig();

                $response = $this->getClient()
                    ->generativeModel(self::MODEL)
                    ->withGenerationConfig($generationConfig)
                    ->generateContent($prompt);

                $nick = $this->sanitizeNickname($response->text());
                $resolve($nick);
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    private function buildPrompt(): TextPart
    {
        return new TextPart(
            "Génère un pseudo unique pour un joueur Minecraft en respectant strictement les règles suivantes :\n" .
            "- Le pseudo doit contenir uniquement des lettres (a-z, A-Z) et des chiffres (0-9).\n" .
            "- Aucun accent, symbole, espace, apostrophe, tiret ou caractère spécial.\n" .
            "- Le pseudo doit faire entre 3 et 16 caractères.\n" .
            "- Inspire-toi du style des pseudos populaires sur NameMC.\n" .
            "- Réponds uniquement avec le pseudo, sans ponctuation, sans phrase, sans guillemets."
        );
    }

    private function buildGenerationConfig(): GenerationConfig
    {
        return (new GenerationConfig())
            ->withTemperature(1.0)
            ->withMaxOutputTokens(20)
            ->withTopK(40)
            ->withTopP(0.9);
    }

    private function sanitizeNickname(string $nick): string
    {
        $nick = preg_replace('/[^a-zA-Z0-9]/', '', $nick);
        return trim($nick);
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(self::API_KEY);
        }

        return $this->client;
    }

    public function isValid(string $nick): bool|Generator
    {
        if (! $this->basicValidation($nick)) {
            return false;
        }

        return $this->validateWithAI($nick);
    }

    private function basicValidation(string $nick): bool
    {
        $length = strlen($nick);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9]+$/', $nick) === 1;
    }

    private function validateWithAI(string $nick): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($nick) {
            try {
                $prompt = $this->buildValidatorPrompt($nick);
                $generationConfig = $this->buildGenerationConfig();

                $response = $this->getClient()
                    ->generativeModel(self::MODEL)
                    ->withGenerationConfig($generationConfig)
                    ->generateContent($prompt);

                $resolve(trim($response->text()) === "1");
            } catch (Throwable) {
                $reject($this->basicValidation($nick));
            }
        });
    }

    private function buildValidatorPrompt(string $nick): TextPart
    {
        return new TextPart(
            "Est-ce que le pseudo suivant est valide pour Minecraft : \"$nick\" ?\n" .
            "Réponds uniquement par 1 si le pseudo est valide, ou 0 s'il ne l'est pas.\n" .
            "Un pseudo valide doit avoir entre " . self::MIN_LENGTH . " et " . self::MAX_LENGTH . " caractères, " .
            "et ne contenir que des lettres (a-z, A-Z) et des chiffres (0-9).\n" .
            "N'ajoute rien d'autre, aucun mot ni ponctuation, juste le chiffre 1 ou 0 sur une seule ligne."
        );
    }

    public function setNick(Player $player, string $nick): void
    {
        $ev = new PlayerNickChangeEvent($player, $nick);
        $ev->call();
        if($ev->isCancelled()){
            MessagesUtils::sendTo($player, MessagesIds::NICK_CANCELLED);
            return;
        }

        self::$nicks[$player->getName()] = $nick;
        $this->addLog($player->getName(), $nick);
        $player->setDisplayName($nick);
        MessagesUtils::sendTo($player, MessagesIds::NICK_SUCCESS, [
            ExtraTags::NICK => $nick
        ]);
    }

    public static function hasNick(Player $player): bool
    {
        return isset(self::$nicks[$player->getName()]) || $player->getDisplayName() !== $player->getName();
    }

    public function resetNick(Player $player): void
    {
        if ($this->hasNick($player)) {
            $ev = new PlayerNickResetEvent($player);
            $ev->call();
            unset(self::$nicks[$player->getName()]);
            $player->setDisplayName($player->getName());
            MessagesUtils::sendTo($player, MessagesIds::NICK_RESET);
        }
    }

    private static array $log = [];

    public function addLog(string $playerName, string $nickname): void {
        $key = strtolower($playerName);
        self::$log[$key][] = [
            "nick" => $nickname,
            "timestamp" => time()
        ];
    }

    public function getLog(string $playerName): ?array {
        return self::$log[strtolower($playerName)] ?? null;
    }

    public function logs(): array {
        return self::$log;
    }

    public static function getNick(string $playerName): ?string
    {
        return self::$nicks[$playerName] ?? null;
    }

}