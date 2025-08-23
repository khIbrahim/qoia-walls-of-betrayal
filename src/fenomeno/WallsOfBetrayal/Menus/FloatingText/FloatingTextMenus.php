<?php

namespace fenomeno\WallsOfBetrayal\Menus\FloatingText;

use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Exceptions\FloatingText\FloatingTextAlreadyExistsException;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomForm;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\CustomFormResponse;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Input;
use fenomeno\WallsOfBetrayal\libs\dktapps\pmforms\element\Label;
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
use pocketmine\world\sound\PopSound;
use Throwable;

class FloatingTextMenus
{

    private const FIELD_LABEL = 'label';
    private const FIELD_NEW_LINE = 'new_line';
    private const FIELD_DELETE_LINE = 'delete_line';
    private const FIELD_FINISH = 'finish';

    private static array $sessions = [];

    public static function sendCreateMenuTo(Player $player, string $id): void
    {
        $menu = new CustomForm("FloatingText - Create {FLOATING_TEXT}", [
            new Input("line1", "§7Line 1", "", ""),
            new Input("line2", "§7Line 2 (optional)", "", ""),
            new Input("line3", "§7Line 3 (optional)", "", ""),
            new Input("line4", "§7Line 4 (optional)", "", ""),
            new Input("line5", "§7Line 5 (optional)", "", ""),
        ], function(Player $player, CustomFormResponse $response) use($id): void {
            $line1 = $response->getString("line1");
            $line2 = $response->getString("line2");
            $line3 = $response->getString("line3");
            $line4 = $response->getString("line4");
            $line5 = $response->getString("line5");

            if($line1 === ""){
                MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_MISSING_LINE);
                return;
            }

            $lines = array_filter([$line1, $line2, $line3, $line4, $line5], fn($l) => $l !== "");
            $text  = implode("\n", $lines);
            $text  = str_replace('\n', "\n", $text);
            $pos   = $player->getPosition();

            Await::f2c(function () use ($player, $pos, $text, $id) {
                try {
                    /** @var FloatingText $floatingText */
                    $floatingText = yield from Main::getInstance()->getFloatingTextManager()->create($id, $pos, $text);
                    $floatingText->sendTo($player);

                    $player->broadcastSound(new PopSound());
                    MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_CREATE_SUCCESS, [ExtraTags::FLOATING_TEXT => $id]);
                } catch (FloatingTextAlreadyExistsException){
                    MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_ALREADY_EXISTS, [ExtraTags::FLOATING_TEXT => $id]);
                } catch (Throwable $e){Utils::onFailure($e, $player, "Failed to create floating text $id, text: $text, pos: {$pos->__toString()} for {$player->getName()}: " . $e->getMessage());}
            });
        });
        $player->sendForm($menu);
    }

    public static function sendEditMenu(Player $player, FloatingText $floatingText): void
    {
        $lines = explode("\n", $floatingText->getText());
        self::$sessions[$player->getName()] ??= $lines;

        $currentLines = self::$sessions[$player->getName()] ?? explode("\n", $floatingText->getText());

        $form = new CustomForm(
            title: MessagesUtils::getMessage(MessagesIds::FLOATING_TEXT_EDIT_MENU_TITLE),
            elements: [
                new Label(self::FIELD_LABEL, MessagesUtils::getMessage(MessagesIds::FLOATING_TEXT_EDIT_MENU_CURRENT_TEXT, [
                    ExtraTags::LINES => empty($currentLines) ? "§c(empty)" : implode("\n", $currentLines)
                ])),
                new Input(self::FIELD_NEW_LINE, MessagesUtils::getMessage(MessagesIds::FLOATING_TEXT_EDIT_MENU_ADD_LINE)),
                new Toggle(self::FIELD_DELETE_LINE, MessagesUtils::getMessage(MessagesIds::FLOATING_TEXT_EDIT_MENU_DELETE_LINE), false),
                new Toggle(self::FIELD_FINISH, MessagesUtils::getMessage(MessagesIds::FLOATING_TEXT_EDIT_MENU_FINISH), false),
            ],
            onSubmit: function(Player $player, CustomFormResponse $response) use ($floatingText): void {
                $name    = $player->getName();
                $manager = Main::getInstance()->getFloatingTextManager();

                $lines         = self::$sessions[$name] ?? explode("\n", $floatingText->getText());
                $newLine       = $response->getString(self::FIELD_NEW_LINE);
                $removeLast    = $response->getBool(self::FIELD_DELETE_LINE);
                $finish        = $response->getBool(self::FIELD_FINISH);

                if($removeLast && !empty($lines)){
                    array_pop($lines);
                    MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_DELETED_LAST_LINE);
                    $player->sendMessage("§aLa dernière ligne sera retirée à la sauvegarde.");
                    self::$sessions[$name] = $lines;
                    self::sendEditMenu($player, $floatingText);
                    return;
                }

                if($newLine !== ""){
                    if(empty($lines) || $lines[array_key_last($lines)] !== $newLine){
                        $lines[] = $newLine;
                    }
                }

                self::$sessions[$name] = $lines;

                if($finish){
                    if(empty($lines)){
                        MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_MISSING_LINE);
                        return;
                    }

                    $text = implode("\n", $lines);

                    Await::f2c(function () use ($name, $player, $text, $manager, $floatingText, $lines) {
                        try {
                            /** @var FloatingText $floatingText */
                            $floatingText = yield from $manager->updateFloatingTextText($floatingText, $text);

                            $floatingText->sendTo($player);
                            unset(self::$sessions[$name]);

                            MessagesUtils::sendTo($player, MessagesIds::FLOATING_TEXT_EDIT_SUCCESS, [ExtraTags::FLOATING_TEXT => $floatingText->getId()]);
                        } catch (Throwable $e){Utils::onFailure($e, $player, 'Failed to edit floating text ' . $floatingText->getId() . ' by ' . $player->getName() . ': ' . $e->getMessage());}
                    });
                } else {
                    self::sendEditMenu($player, $floatingText);
                }
            }
        );

        $player->sendForm($form);
    }

    public static function sendListMenu(Player $player): void
    {
        $texts = Main::getInstance()->getFloatingTextManager()->getAll();

        $options = [];
        foreach($texts as $id => $data){
            $options[] = new MenuOption($id . "\n§7See details");
        }
        $options[] = new MenuOption("§cQuit");

        $menu = new MenuForm(
            title: "§7FloatingText List",
            text: "§7Choose a floating text to edit it:",
            options: $options,
            onSubmit: function(Player $player, int $selectedOption) use ($texts): void{
                $keys = array_keys($texts);

                $selectedId = $keys[$selectedOption] ?? null;
                if($selectedId === null){
                    MessagesUtils::sendTo($player, MessagesIds::UNKNOWN_FLOATING_TEXT, [ExtraTags::FLOATING_TEXT => $selectedId]);
                    return;
                }

                $floatingText = Main::getInstance()->getFloatingTextManager()->getFloatingText($selectedId);

                if ($floatingText === null) {
                    MessagesUtils::sendTo($player, MessagesIds::UNKNOWN_FLOATING_TEXT, [ExtraTags::FLOATING_TEXT => $selectedId]);
                    return;
                }

                self::sendEditMenu($player, $floatingText);
            }
        );

        $player->sendForm($menu);
    }
}