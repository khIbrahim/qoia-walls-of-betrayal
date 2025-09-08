<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\FloatingText;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\CreateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\FloatingText\UpdateFloatingTextPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\IdPayload;
use fenomeno\WallsOfBetrayal\Exceptions\FloatingText\FloatingTextAlreadyExistsException;
use fenomeno\WallsOfBetrayal\Exceptions\FloatingText\UnknownFloatingTextException;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use Throwable;

final class FloatingTextManager
{

    /** @var FloatingText[] */
    private array $floatingTexts = [];

    public function __construct(private readonly Main $main)
    {
        $this->load();
    }

    private function load(): void
    {
        $this->main->getDatabaseManager()->getFloatingTextRepository()->load(
            function (array $floatingTexts){
                $this->floatingTexts = $floatingTexts;

                $this->main->getLogger()->info("§aFloatingTexts - Loaded " . count($floatingTexts) . ' (' . implode(', ', array_map(fn(FloatingText $f) => $f->getId(), $floatingTexts)) . ')');
                $this->main->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->updateAllFloatingTexts()), 20);
            },
            function (Throwable $e){
                $this->main->getLogger()->error("Failed to load floating texts: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    /**
     * @throws FloatingTextAlreadyExistsException
     */
    public function create(string $id, Position $position, string $text, bool $save = true): Generator
    {
        if ($this->exists($id)){
            throw new FloatingTextAlreadyExistsException("FloatingText with id $id already exists");
        }

        $floatingText = new FloatingText($id, $position, $text);

        if ($save) {
            yield from $this->main->getDatabaseManager()->getFloatingTextRepository()->create(CreateFloatingTextPayload::fromObject($floatingText));

            $this->floatingTexts[$id] = $floatingText;
        }

        return $floatingText;
    }

    /**
     * @throws UnknownFloatingTextException
     */
    public function sendFloatingText(Player $player, string $id): void
    {
        if (! $this->exists($id)) {
            throw new UnknownFloatingTextException("Unknown floating text $id");
        }

        $floatingText = $this->floatingTexts[$id];
        if ($player->getWorld()->getFolderName() !== $floatingText->getPosition()->getWorld()->getFolderName()) {
            return;
        }

        $this->cleanupFloatingText($id, $player);
        $floatingText->sendTo($player);
    }

    public function updateFloatingText(Player $player, string $id): void
    {
        if (! $this->exists($id)) {
            return;
        }

        $floatingText = $this->floatingTexts[$id];
        if ($player->getWorld()->getFolderName() !== $floatingText->getPosition()->getWorld()->getFolderName()) {
            return;
        }

        $floatingText->updateFor($player);
    }

    public function updateAllFloatingTexts(): void
    {
        foreach ($this->main->getServer()->getOnlinePlayers() as $player) {
            foreach ($this->floatingTexts as $id => $_) {
                $this->updateFloatingText($player, $id);
            }
        }
    }

    /**
     * @throws UnknownFloatingTextException
     */
    public function removeFloatingText(string $id): Generator
    {
        if (! isset($this->floatingTexts[$id])) {
            throw new UnknownFloatingTextException("Unknown floating text $id");
        }

        yield from $this->main->getDatabaseManager()->getFloatingTextRepository()->remove(new IdPayload($id));

        $this->cleanupFloatingText($id);
        unset($this->floatingTexts[$id]);

        return $id;
    }

    public function exists(string $id): bool
    {
        return isset($this->floatingTexts[$id]);
    }

    public function cleanup(): void
    {
        foreach ($this->floatingTexts as $id => $_) {
            $this->cleanupFloatingText($id);
        }

        $this->floatingTexts = [];
    }

    /** @return FloatingText[] */
    public function getAll(): array
    {
        return $this->floatingTexts;
    }

    public function getFloatingText(string $id): ?FloatingText
    {
        return $this->floatingTexts[$id] ?? null;
    }

    public function cleanupFloatingText(string $id, ?Player $player = null): void
    {
        if ($player !== null){
            if (isset($this->floatingTexts[$id])) {
                $player->getNetworkSession()->sendDataPacket(
                    RemoveActorPacket::create($this->floatingTexts[$id]->getRuntimeId())
                );
            }
            return;
        }

        foreach ($this->main->getServer()->getOnlinePlayers() as $onlinePlayer) {
            if (isset($this->floatingTexts[$id])) {
                $onlinePlayer->getNetworkSession()->sendDataPacket(
                    RemoveActorPacket::create($this->floatingTexts[$id]->getRuntimeId())
                );
            }
        }
    }

    public function updateFloatingTextText(FloatingText $floatingText, $text): Generator
    {
        yield from $this->main->getDatabaseManager()->getFloatingTextRepository()->updateText(new UpdateFloatingTextPayload($floatingText->getId(), $text));

        $this->cleanupFloatingText($floatingText->getId());

        $floatingText->setText($text);
        $this->floatingTexts[$floatingText->getId()] = $floatingText;

        return $floatingText;
    }

}