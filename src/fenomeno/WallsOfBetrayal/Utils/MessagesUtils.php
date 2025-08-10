<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use ReflectionClass;

class MessagesUtils {

    public static Config $config;
    private static array $colorTags = [];

    public static function init(PluginBase $plugin, string $config = 'messages.yml') : void {
        $plugin->saveResource($config, true);
        self::$config = new Config($plugin->getDataFolder() . $config, Config::YAML);
        foreach ((new ReflectionClass(TextFormat::class))->getConstants() as $color => $code) {
            if (is_string($code)) static::$colorTags["{" . $color . "}"] = $code;
        }
    }

    public static function sendTo(Player|Server|array $player, string $id, array $extraTags = [], ?string $default = null) : void {
        $message = self::getMessage($id, $extraTags, $default ?? $id);
        if ($message === "") return;

        $type = self::$config->getNested($id . '.type', 'message');

        if (is_array($player)) {
            foreach ($player as $p) {
                if ($p instanceof Player) {
                    self::sendTo($p, $id, $extraTags, $default);
                }
            }
            return;
        }

        if ($player instanceof Player) {
            match ($type) {
                'title' => $player->sendTitle($message),
                'popup' => $player->sendPopup($message),
                'tip' => $player->sendTip($message),
                'toast' => $player->sendToastNotification(
                    explode("\n", $message)[0] ?? "",
                    explode("\n", $message)[1] ?? ""
                ),
                'bar' => $player->sendActionBarMessage($message),
                default => $player->sendMessage($message),
            };
            return;
        }

        if ($player instanceof Server) {
            match ($type) {
                'title' => $player->broadcastTitle($message),
                'popup' => $player->broadcastPopup($message),
                'tip' => $player->broadcastTip($message),
                default => $player->broadcastMessage($message),
            };
        }
    }

    public static function getMessage(string $id, array $extraTags = [], ?string $default = null) : string {
        $default ??= $id;
        if (self::$config->getNested($id.'.message') !== null) {
            $message = (string)self::$config->getNested($id.'.message', $default);
        }
        else if (self::$config->getNested($id) !== null) {
            $message = (string)self::$config->getNested($id, $default);
        }
        else {
            $message = (string)self::$config->get($id, $default);
        }
        $message = self::translateColorTags($message);
        return str_replace(array_keys($extraTags), $extraTags, $message);
    }

    public static function translateColorTags(string $message): string
    {
        return str_replace(array_keys(static::$colorTags), static::$colorTags, TextFormat::colorize($message));
    }

}