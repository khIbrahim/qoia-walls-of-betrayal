<?php

namespace fenomeno\WallsOfBetrayal\Manager\Punishment;

use DateTime;
use fenomeno\WallsOfBetrayal\Class\Punishment\Ban;
use fenomeno\WallsOfBetrayal\Database\Payload\UsernamePayload;
use fenomeno\WallsOfBetrayal\Events\Punishment\PlayerBannedEvent;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerAlreadyBannedException;
use fenomeno\WallsOfBetrayal\Exceptions\Punishment\PlayerNotBannedException;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Services\NotificationService;
use Generator;
use Throwable;

class BanManager
{

    /** @var Ban[] */
    private array $bans;

    public function __construct(private readonly Main $main)
    {
        foreach (['ban', 'ban-ip', 'pardon', 'banlist', 'kick'] as $cmd){
            $this->main->getServer()->getCommandMap()->unregister(
                $this->main->getServer()->getCommandMap()->getCommand($cmd)
            );
        }

        $this->load();
    }

    private function load(): void
    {
        Await::g2c(
            $this->main->getDatabaseManager()->getBanRepository()->getAll(),
            function (array $bans) {
               $this->bans = $bans;

               $this->removeExpired();
            }, function(Throwable $e) {
                $this->main->getLogger()->error("Failed to load bans: " . $e->getMessage());
                $this->main->getLogger()->logException($e);
            }
        );
    }

    /**
     * @throws PlayerAlreadyBannedException
     */
    public function banPlayer(string $target, string $reason = "", string $staff = "", ?int $expiration = null, bool $silent = false): Generator
    {
        $target = strtolower($target);

        if($this->isBanned($target)){
            throw new PlayerAlreadyBannedException("Player $target is already banned.");
        }

        /** @var Ban $ban */
        $ban = yield from $this->main->getDatabaseManager()->getBanRepository()->create(new Ban($target, $reason, $staff, $expiration >= PHP_INT_MAX ? null : $expiration, $silent));

        $this->bans[strtolower($target)] = $ban;

        $this->main->getServer()->getNameBans()->addBan($target, $reason, DateTime::createFromFormat('U', $ban->getExpiration() ?? 0), $staff);
        $this->main->getServer()->getIPBans()->addBan($target, $reason, DateTime::createFromFormat('U', $ban->getExpiration() ?? 0), $staff);

        (new PlayerBannedEvent($ban))->call();

        if(! $ban->isSilent()){
            NotificationService::broadcastBan($ban);
        }

        return $ban;
    }

    /** @throws */
    public function removeExpired(): void
    {
        foreach ($this->bans as $ban){
            if ($ban->isExpired()){
                Await::g2c(
                    $this->unbanPlayer($ban->getTarget()),
                    fn(string $target) => $this->main->getLogger()->info("Removed Expired Ban: " . $target),
                    function(Throwable $e) use ($ban) {
                        $this->main->getLogger()->error("Failed to remove expired ban of : " . $ban->getTarget());
                        $this->main->getLogger()->logException($e);
                    }
                );
            }
        }
    }

    /**
     * @throws PlayerNotBannedException
     */
    public function unbanPlayer(string $target): Generator
    {
        $target = strtolower($target);
        if(! $this->isBanned($target)){
            throw new PlayerNotBannedException("Player $target is not banned.");
        }

        $this->main->getServer()->getNameBans()->remove($target);
        $this->main->getServer()->getIPBans()->remove($target);

        yield from $this->main->getDatabaseManager()->getBanRepository()->delete(new UsernamePayload(strtolower($target)));

        unset($this->bans[$target]);

        NotificationService::broadcastUnban($target);

        return $target;
    }

    public function isBanned(string $target): bool
    {
        return isset($this->bans[strtolower($target)]);
    }

    public function getBan(string $target): ?Ban
    {
        return $this->ban[strtolower($target)] ?? null;
    }

    public function getActiveBans(): array
    {
        return array_filter($this->bans, fn(Ban $ban) => $ban->isActive());
    }

}