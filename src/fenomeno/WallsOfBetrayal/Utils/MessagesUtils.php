<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\command\CommandSender;
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

    public static function sendTo(CommandSender $player, string $id, array $extraTags = [], ?string $default = null) : void {
        $message = self::getMessage($id, $default ?? $id, $extraTags);
        if ($message === "")
            return;
        if ($player instanceof Player){
            match(self::$config->getNested($id.'.type')){
                'title' => $player->sendTitle($message),
                'popup' => $player->sendPopup($message),
                'tip' => $player->sendTip($message),
                default => $player->sendMessage($message)
            };
        } elseif($player instanceof Server){
            match(self::$config->getNested($id.'.type')){
                'title' => $player->broadcastTitle($message),
                'popup' => $player->broadcastPopup($message),
                'tip' => $player->broadcastTip($message),
                default => $player->broadcastMessage($message)
            };
        } else $player->sendMessage($message);
    }

    public static function getMessage(string $id, ?string $default = null, array $extraTags = []) : string {
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