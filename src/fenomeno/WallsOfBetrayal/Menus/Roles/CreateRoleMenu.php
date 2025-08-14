<?php

namespace fenomeno\WallsOfBetrayal\Menus\Roles;

use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use pocketmine\player\Player;

class CreateRoleMenu
{

    public static function sendTo(Player $player, string $id): void
    {
        $menu = new MenuForm("TODO $id", "TODO $id", [new MenuOption("TODO $id")], function(Player $player, int $selectedOption) use ($id): void{
            $player->sendMessage("TODO $id $selectedOption");
        });
        $player->sendForm($menu);
    }

}