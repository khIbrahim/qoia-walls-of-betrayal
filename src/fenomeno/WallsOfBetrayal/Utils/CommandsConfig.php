<?php

namespace fenomeno\WallsOfBetrayal\Utils;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

class CommandsConfig
{

    /** @var CommandDTO[] */
    private static array $commandsDTO = [];

    public static function init(Main $main): void
    {
        $main->saveResource('commands.yml', true);
        $data = (new Config($main->getDataFolder() . 'commands.yml', Config::YAML))->getAll();
        self::registerCommandsDTO($data);
        $main->getLogger()->info(TextFormat::GREEN . "Registered (" . count(self::$commandsDTO) . ") commands DTO");
    }

    public static function registerCommandsDTO(array $data): void
    {
        foreach($data as $commandId => $commandData){
            try {
                $name = (string) ($commandData['name'] ?? $commandId);
                $description = (string) ($commandData['description'] ?? "");
                $usage = (string) ($commandData['usage'] ?? "/" . $commandId);
                $aliases = (array) ($commandData['aliases'] ?? []);

                self::$commandsDTO[$commandId] = new CommandDTO($name, $description, $usage, $aliases);
            } catch (Throwable $e){
                Main::getInstance()->getLogger()->error("Failed to parse command DTO $commandId: " . $e->getMessage());
            }
        }
    }

    public static function getCommandById(string $id) : CommandDTO
    {
        return self::$commandsDTO[$id];
    }

}