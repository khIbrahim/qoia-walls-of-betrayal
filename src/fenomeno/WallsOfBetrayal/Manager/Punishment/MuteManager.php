<?php

namespace fenomeno\WallsOfBetrayal\Manager\Punishment;

use fenomeno\WallsOfBetrayal\Class\Punishment\Mute;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\Events\Punishment\PlayerMutedEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyMutedException;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotMutedException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Services\NotificationService;
use Generator;
use Throwable;

class MuteManager
{

    /** @var Mute[] */
    private array $mutes = [];

    public function __construct(private readonly Main $main)
    {
        $this->load();
    }

    private function load(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getMuteRepository()->getAll(),
            function (array $mutes) {
                $this->mutes = $mutes;

                $this->removeExpired();
            }, function(Throwable $e) {
                $this->main->getLogger()->error("Failed to load mutes: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    /** @throws */
    public function removeExpired(): void
    {
        foreach ($this->mutes as $mute){
            if ($mute->isExpired()){
                Await::g2c(
                    $this->unmutePlayer($mute->getTarget()),
                    fn(string $target) => $this->main->getLogger()->info("Removed Expired Mute: " . $target),
                    function(Throwable $e) use ($mute) {
                        $this->main->getLogger()->error("Failed to remove expired mute of : " . $mute->getTarget());
                        $this->main->getLogger()->logException($e);
                    }
                );
            }
        }
    }

    /**
     * @throws PlayerNotMutedException
     */
    public function unmutePlayer(string $player): Generator
    {
        if(! isset($this->mutes[strtolower($player)])){
            throw new PlayerNotMutedException("Player $player is not muted.");
        }

        yield from $this->main->getDatabaseManager()->getMuteRepository()->delete(new UsernamePayload(strtolower($player)));

        unset($this->mutes[strtolower($player)]);

        NotificationService::broadcastUnmute($player);

        return $player;
    }

    public function isMuted(string $target): bool
    {
        return isset($this->mutes[strtolower($target)]) && $this->mutes[strtolower($target)]->isActive();
    }

    /**
     * @throws PlayerAlreadyMutedException
     */
    public function mutePlayer(string $target, string $reason, string $staff, ?int $expiration = null): Generator
    {
        $target = strtolower($target);

        if ($this->isMuted($target)) {
            throw new PlayerAlreadyMutedException("Player $target is already muted.");
        }

        /** @var Mute $mute */
        $mute = yield from $this->main->getDatabaseManager()->getMuteRepository()->create(new Mute($target, $reason, $staff, $expiration >= PHP_INT_MAX ? null : $expiration));

        $this->mutes[$target] = $mute;

        (new PlayerMutedEvent($mute))->call();

        NotificationService::broadcastMute($mute);

        return $mute;
    }

    public function getMute(string $target): ?Mute
    {
        return $this->mutes[$target] ?? null;
    }

    public function getActiveMutes(): array
    {
        return array_filter($this->mutes, fn(Mute $mute) => $mute->isActive());
    }

}