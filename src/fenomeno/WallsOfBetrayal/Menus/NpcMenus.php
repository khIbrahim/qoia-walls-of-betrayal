<?php
declare(strict_types=1);

namespace fenomeno\WallsOfBetrayal\Menus;

use fenomeno\WallsOfBetrayal\Entities\Types\NpcEntity;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomFormResponse;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Input;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Slider;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Toggle;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\MenuOption;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use pocketmine\player\Player;
use Throwable;

final class NpcMenus {

    private const FIELD_NAME = 'name';
    private const FIELD_COMMAND = 'command';
    private const FIELD_SKIN = 'skin_url';
    private const FIELD_COOLDOWN = 'cooldown';

    public static function sendCreateMenu(Player $player, string $id): void {
        $menu = new CustomForm(
            title: MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_TITLE, [ExtraTags::ID => $id]),
            elements: [
                new Input(self::FIELD_NAME, MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_NAME_INPUT), "Guide"),
                new Input(self::FIELD_COMMAND, MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_COMMAND_INPUT), MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_COMMAND_HIDDEN_INPUT)),
                new Slider(self::FIELD_COOLDOWN, MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_COOLDOWN_INPUT), 0.0, 60.0, 1.0, (float) MessagesUtils::getMessage(MessagesIds::NPC_CREATE_MENU_COOLDOWN_INPUT)),
            ],
            onSubmit: function(Player $player, CustomFormResponse $response) use ($id): void {
                $name     = trim($response->getString(self::FIELD_NAME));
                $command  = trim($response->getString(self::FIELD_COMMAND));
                try {
                    $cooldown = $response->getInt(self::FIELD_COOLDOWN);
                } catch (Throwable){
                    $cooldown = $response->getFloat(self::FIELD_COOLDOWN);
                } finally {
                    $cooldown = (int) $cooldown;
                }

                if ($name === ""){
                    MessagesUtils::sendTo($player, MessagesIds::NPC_CREATE_MENU_NAME_EMPTY);
                    return;
                }

                if ($command === ""){
                    MessagesUtils::sendTo($player, MessagesIds::NPC_CREATE_MENU_COMMAND_EMPTY);
                    return;
                }

                try {
                    $skin = $player->getSkin();

                    $npc = NpcEntity::make(
                        location: $player->getLocation(),
                        skin: $skin,
                        id: $id,
                        command: $command,
                        name: $name,
                        cooldown: $cooldown
                    );
                    $npc->spawnToAll();
                    Await::g2c(
                        Main::getInstance()->getNpcManager()->add($npc),
                        fn (NpcEntity $npc) => MessagesUtils::sendTo($player, MessagesIds::NPC_CREATE_MENU_SUCCESS, [ExtraTags::NPC => $id]),
                        fn (Throwable $e)   => Utils::onFailure($e, null, "Failed to add npc $id to the manager from the init entity:" . $e->getMessage())
                    );
                } catch (Throwable $e){Utils::onFailure($e, $player, "Failed to create npc $id with name: $name, command: $command by {$player->getName()}: " . $e->getMessage());}
            }
        );
        $player->sendForm($menu);
    }

    public static function sendEditMenu(Player $player, NpcEntity $npc): void {
        $menu = new CustomForm(
            title: MessagesUtils::getMessage(MessagesIds::NPC_EDIT_MENU_TITLE, [ExtraTags::NPC => $npc->getNpcId()]),
            elements: [
                new Input(self::FIELD_NAME, MessagesUtils::getMessage(MessagesIds::NPC_EDIT_MENU_NAME_INPUT), $npc->getNameTag(), $npc->getNameTag()),
                new Input(self::FIELD_COMMAND, MessagesUtils::getMessage(MessagesIds::NPC_EDIT_MENU_COMMAND_INPUT), $npc->getStoredCommand(), $npc->getStoredCommand()),
                new Slider(self::FIELD_COOLDOWN, MessagesUtils::getMessage(MessagesIds::NPC_EDIT_MENU_COOLDOWN_INPUT), 0.0, 60.0, 1.0, (float) $npc->getCooldown()),
                new Toggle(self::FIELD_SKIN, MessagesUtils::getMessage(MessagesIds::NPC_EDIT_MENU_SKIN_TOGGLE))
            ],
            onSubmit: function(Player $player, CustomFormResponse $response) use ($npc): void {
                $name       = trim($response->getString(self::FIELD_NAME));
                $command    = trim($response->getString(self::FIELD_COMMAND));
                $updateSkin = $response->getBool(self::FIELD_SKIN);
                try {
                    $cooldown = $response->getInt(self::FIELD_COOLDOWN);
                } catch (Throwable){
                    $cooldown = $response->getFloat(self::FIELD_COOLDOWN);
                } finally {
                    $cooldown = (int) $cooldown;
                }

                Await::f2c(static function () use ($name, $command, $cooldown, $player, $updateSkin, $npc) {
                    try {
                        yield from Main::getInstance()->getNpcManager()->update($npc, function (NpcEntity $npc) use ($updateSkin, $player, $cooldown, $command, $name) {
                            $npc->setNameTag($name);
                            $npc->setCommand($command);
                            $npc->setCooldown($cooldown);
                            if ($updateSkin){
                                $npc->setSkin($player->getSkin());
                                $npc->sendSkin();
                            }

                            MessagesUtils::sendTo($player, MessagesIds::NPC_EDITED, [ExtraTags::NPC => $npc->getNpcId()]);
                        });
                    } catch (Throwable $e){
                        Utils::onFailure($e, $player, 'Failed to update npc ' . $npc->getNpcId() . ' by ' . $player->getName() . ' fields: ' . $name . ', ' . $command . ', ' . (int) $updateSkin . ':' . $e->getMessage());
                    }
                });
            }
        );
        $player->sendForm($menu);
    }

    public static function sendListMenu(Player $player): void {
        $mgr = Main::getInstance()->getNpcManager();
        $all = array_values($mgr->getAll());
        $form = new MenuForm(
            title: MessagesUtils::getMessage(MessagesIds::NPC_LIST_MENU_TITLE),
            text: MessagesUtils::getMessage(MessagesIds::NPC_LIST_MENU_TEXT),
            options: array_map(fn(NpcEntity $npcEntity) => new MenuOption(MessagesUtils::getMessage(MessagesIds::NPC_LIST_MENU_BUTTON, [
                ExtraTags::ID   => $npcEntity->getNpcId(),
                ExtraTags::NAME => $npcEntity->getNameTag()
            ])), $all),
            onSubmit: function(Player $player, int $selectedOption) use($all) : void{
                if(! isset($all[$selectedOption])){
                    MessagesUtils::sendTo($player, MessagesIds::NPC_NOT_FOUND, [ExtraTags::NPC => $selectedOption]);
                    return;
                }

                $npc = $all[$selectedOption];
                self::sendNpcActionsMenu($player, $npc);
            }
        );
        $player->sendForm($form);
    }

    private static function sendNpcActionsMenu(Player $player, NpcEntity $npc): void {
        $menu = new MenuForm(
            title: MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_TITLE),
            text: MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_TEXT),
            options: [
                new MenuOption(MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_TP_BUTTON)),
                new MenuOption(MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_EDIT_BUTTON)),
                new MenuOption(MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_MOVE_BUTTON)),
                new MenuOption(MessagesUtils::getMessage(MessagesIds::NPC_ACTIONS_MENU_REMOVE_BUTTON)),
            ],
            onSubmit: function(Player $player, int $selectedOption) use($npc) : void{
                switch($selectedOption){
                    case 0:
                        $player->chat("/npc tp " . $npc->getNpcId());
                        break;
                    case 1:
                        $player->chat("/npc edit " . $npc->getNpcId());
                        break;
                    case 2:
                        $player->chat("/npc move " . $npc->getNpcId());
                        break;
                    case 3:
                        $player->chat("/npc remove " . $npc->getNpcId());
                        break;
                }
            }
        );

        $player->sendForm($menu);
    }
}