<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use Throwable;

final class MessagesUtils {

    private static Config $config;

    private static array $colorTags = [];

    private static array $themeTags = []; // ex: {GLOBAL_PREFIX} => "§6§lWalls..."

    private static array $templateCache = [];

    private static string $configName = 'messages.yml';

    private static ?PluginBase $plugin = null;

    public static function init(PluginBase $plugin, string $config = 'messages.yml') : void {
        self::$plugin = $plugin;
        self::$configName = $config;

        $plugin->saveResource($config, true);
        self::$config = new Config($plugin->getDataFolder() . $config, Config::YAML);

        self::$colorTags = [];
        foreach ((new ReflectionClass(TextFormat::class))->getConstants() as $color => $code) {
            if (is_string($code)) {
                self::$colorTags["{" . $color . "}"] = $code;
            }
        }

        self::loadThemeTags();

        self::$templateCache = [];
    }

    public static function reload(): void {
        if (self::$plugin === null) return;
        self::init(self::$plugin, self::$configName);
    }

    public static function sendTo(Player|Server|array $target, string $id, array $extraTags = [], ?string $default = null) : void {
        $message = self::getMessage($id, $extraTags, $default ?? $id);
        if ($message === "") return;

        $type = (string) self::$config->getNested($id . '.type', 'message');

        if (is_array($target)) {
            foreach ($target as $p) {
                if ($p instanceof Player) {
                    self::sendTo($p, $id, $extraTags, $default);
                }
            }
            return;
        }

        if ($target instanceof Player) {
            match ($type) {
                'title' => $target->sendTitle($message),
                'popup' => $target->sendPopup($message),
                'tip' => $target->sendTip($message),
                'toast' => $target->sendToastNotification(
                    explode("\n", $message)[0] ?? "",
                    explode("\n", $message)[1] ?? ""
                ),
                'bar' => $target->sendActionBarMessage($message),
                default => $target->sendMessage($message),
            };
            return;
        }

        if ($target instanceof Server) {
            match ($type) {
                'title' => $target->broadcastTitle($message),
                'popup' => $target->broadcastPopup($message),
                'tip' => $target->broadcastTip($message),
                default => $target->broadcastMessage($message),
            };
        }
    }

    public static function getMessage(string $id, array $extraTags = [], ?string $default = null) : string {
        $default ??= $id;

        if (isset(self::$templateCache[$id])) {
            $template = self::$templateCache[$id];
            return !empty($extraTags) ? str_replace(array_keys($extraTags), array_values($extraTags), $template) : $template;
        }

        $raw = self::lookupRawMessage($id, $default);
        $t = self::applyThemeTags($raw);
        $t = self::translateColorTags($t);

        self::$templateCache[$id] = $t;

        return !empty($extraTags) ? str_replace(array_keys($extraTags), array_values($extraTags), $t) : $t;
    }

    public static function translateColorTags(string $message): string {
        if (!empty(self::$colorTags)) {
            $message = str_replace(array_keys(self::$colorTags), self::$colorTags, $message);
        }
        return TextFormat::colorize($message);
    }

    private static function applyThemeTags(string $message): string {
        if (!empty(self::$themeTags)) {
            $message = str_replace(array_keys(self::$themeTags), self::$themeTags, $message);
        }
        return $message;
    }

    private static function lookupRawMessage(string $id, string $default): string {
        $nodeMsg = self::$config->getNested($id . '.message');
        if ($nodeMsg !== null) {
            return (string)$nodeMsg;
        }
        $node = self::$config->getNested($id);
        if (is_string($node)) {
            return $node;
        }
        $root = self::$config->get($id);
        if (is_string($root)) {
            return $root;
        }
        return $default;
    }

    private static function loadThemeTags(): void {
        self::$themeTags = [];

        try {
            $theme    = (array) self::$config->get('theme', []);
            $prefixes = (array) ($theme['prefixes'] ?? []);
            $colors   = (array) ($theme['colors'] ?? []);

            $map = [
                '{GLOBAL_PREFIX}'    => (string)($prefixes['global']    ?? ''),
                '{KINGDOMS_PREFIX}'  => (string)($prefixes['kingdoms']  ?? ''),
                '{KITS_PREFIX}'      => (string)($prefixes['kits']      ?? ''),
                '{ABILITIES_PREFIX}' => (string)($prefixes['abilities'] ?? ''),
                '{SHOP_PREFIX}'      => (string)($prefixes['shop']      ?? '')
            ];
            foreach ($map as $k => $v) {
                if ($v !== '') self::$themeTags[$k] = $v;
            }

            foreach ($colors as $name => $code) {
                $placeholder = '{' . strtoupper((string)$name) . '}';
                self::$colorTags[$placeholder] = (string)$code;
            }
        } catch (Throwable) {}
    }
}