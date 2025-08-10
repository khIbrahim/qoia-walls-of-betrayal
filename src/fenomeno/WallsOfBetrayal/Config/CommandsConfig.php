<?php

namespace fenomeno\WallsOfBetrayal\Config;

use fenomeno\WallsOfBetrayal\DTO\CommandDTO;
use fenomeno\WallsOfBetrayal\Main;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Throwable;

final class CommandsConfig
{
    /** @var CommandDTO[] */
    private static array $commandsDTO = [];

    private const COMMAND_PREFIX = "§r§6§lWOB §8»§r ";

    public static function init(Main $main): void
    {
        $main->saveResource('commands.yml', true);
        $configPath = $main->getDataFolder() . 'commands.yml';

        $data = (new Config($configPath, Config::YAML))->getAll();
        self::registerCommandsDTO($data);

        $commandsNames = implode(', ', array_map(fn(CommandDTO $commandDTO) => $commandDTO->name, self::$commandsDTO));
        $main->getLogger()->info(TextFormat::GREEN . "Registered (" . count(self::$commandsDTO) . ") commands DTO: (" . $commandsNames . ")");
    }

    private static function registerCommandsDTO(array $data): void
    {
        foreach ($data as $commandId => $commandData) {
            try {
                $name        = (string)($commandData['name'] ?? $commandId);
                $description = self::applyTheme((string)($commandData['description'] ?? ""));
                $usage       = self::applyTheme((string)($commandData['usage'] ?? "/" . $commandId));
                $aliases     = array_values((array)($commandData['aliases'] ?? []));

                if ($name === '') {
                    throw new \InvalidArgumentException("Command name is empty for ID: $commandId");
                }

                self::$commandsDTO[$commandId] = new CommandDTO(
                    $name,
                    $description,
                    $usage,
                    $aliases
                );
            } catch (Throwable $e) {
                Main::getInstance()->getLogger()->error(
                    "Failed to parse command DTO '$commandId': " . $e->getMessage()
                );
            }
        }
    }

    public static function getCommandById(string $id): ?CommandDTO
    {
        return self::$commandsDTO[$id] ?? null;
    }

    private static function applyTheme(string $text): string
    {
        return self::COMMAND_PREFIX . $text;
    }
}