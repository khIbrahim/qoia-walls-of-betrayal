<?php

namespace fenomeno\WallsOfBetrayal\Economy;

use Closure;
use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\AddEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\GetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\InsertEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SubtractEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TopEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TransferEconomyPayload;
use fenomeno\WallsOfBetrayal\Economy\Currency\Currency;
use fenomeno\WallsOfBetrayal\Events\Economy\AddBalanceEvent;
use fenomeno\WallsOfBetrayal\Events\Economy\EconomyEvent;
use fenomeno\WallsOfBetrayal\Events\Economy\InsertBalanceEvent;
use fenomeno\WallsOfBetrayal\Events\Economy\SetBalanceEvent;
use fenomeno\WallsOfBetrayal\Events\Economy\SubtractBalanceEvent;
use fenomeno\WallsOfBetrayal\Events\Economy\TransferBalanceEvent;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordAlreadyExistsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InvalidEconomyAmount;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use Throwable;

class EconomyManager
{
    private Currency $currency;

    /** @var array<string, EconomyEntry> */
    private array $cache = [];

    private bool $dirty = false;

    public function __construct(private readonly Main $main)
    {
        $this->initCurrency();
    }

    /**
     * @return Generator<EconomyEntry>
     * @throws
     */
    public function get(Player|string $name, ?string $uuid = null, bool $safe = false): Generator
    {
        $key = $this->normalizeKey($name);
        if (!$uuid && $name instanceof Player) {
            $uuid = $name->getUniqueId()->toString();
        }

        if ($this->isInCache($name)){
            return $this->cache[$key];
        }

        try {
            $entry = yield from $this->main->getDatabaseManager()
                ->getEconomyRepository()
                ->get(new GetEconomyPayload($key, $uuid));

            $this->cache[$key] = $entry;
            $this->dirty = true;
            $this->sort();

            return $entry;
        } catch (Throwable $e) {
            if ($safe) {
                return new EconomyEntry($name instanceof Player ? $name->getName() : $name, $uuid, 0);
            }
            throw $e;
        }
    }

    public function insert(string $name, string $uuid, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        $key = strtolower($name);
        $event = new InsertBalanceEvent($name, $uuid);

        $onSuccess ??= static function(): void {};
        $onFailure ??= function(Throwable $e) use ($event): void {
            $event->cancel();
            throw $e;
        };

        $event->call();
        if ($event->isCancelled()) return;

        try {
            Await::g2c(
                $this->main->getDatabaseManager()->getEconomyRepository()->insert(
                    new InsertEconomyPayload($event->getUsername(), $event->getUuid())
                ),
                function() use ($event, $key, $onSuccess): void {
                    $entry = new EconomyEntry($event->getUsername(), $event->getUuid(), $this->currency->defaultAmount);
                    $this->cache[$key] = $entry;
                    $this->dirty = true;
                    $this->sort();
                    $onSuccess();
                },
                $onFailure
            );
        } catch (DatabaseException $e) {
            $event->cancel();
            $this->main->getLogger()->info("ECONOMY - Failed to insert {$event->getUsername()}: " . $e->getPrevious()?->getMessage());
            $this->main->getLogger()->logException($e);
        } catch (EconomyRecordAlreadyExistsException $e) {
            $event->cancel();
            $this->main->getLogger()->warning($e->getMessage());
        } catch (Throwable) {
            $event->cancel();
        }
    }

    /**
     * @throws InvalidEconomyAmount|EconomyRecordNotFoundException|DatabaseException|Throwable
     */
    public function add(string|Player $player, int $amount): Generator
    {
        if ($amount <= 0) throw new InvalidEconomyAmount();

        [$username, $uuid] = $this->extractPlayerData($player);
        $key = $this->normalizeKey($player);

        $event = new AddBalanceEvent($username, $uuid, $amount);

        yield from $this->handleEvent($event, function(?AddBalanceEvent $ev) use ($key) {
            if($ev){
                yield from $this->main->getDatabaseManager()
                    ->getEconomyRepository()
                    ->add(new AddEconomyPayload(
                        amount: $ev->getAmount(),
                        username: $ev->getUsername(),
                        uuid: $ev->getUuid()
                    ));

                $this->modifyCache($key, $ev->getAmount());
                $this->sort();
            }
        });
    }

    /**
     * @throws InvalidEconomyAmount|EconomyRecordNotFoundException|InsufficientFundsException|Throwable
     */
    public function subtract(Player|string $player, int $amount): Generator
    {
        if ($amount <= 0) throw new InvalidEconomyAmount();

        [$username, $uuid] = $this->extractPlayerData($player);
        $key = $this->normalizeKey($player);

        $event = new SubtractBalanceEvent($username, $uuid, $amount);

        yield from $this->handleEvent($event, function(?SubtractBalanceEvent $ev) use ($key) {
            if ($ev){
                yield from $this->main->getDatabaseManager()
                    ->getEconomyRepository()
                    ->subtract(new SubtractEconomyPayload(
                        amount: $ev->getAmount(),
                        username: $ev->getUsername(),
                        uuid: $ev->getUuid()
                    ));

                $this->modifyCache($key, -$ev->getAmount());
                $this->sort();
            }
        });
    }

    /**
     * @throws InvalidEconomyAmount|EconomyRecordNotFoundException|InsufficientFundsException|DatabaseException|Throwable
     */
    public function transfer(string|Player $from, string|Player $to, int $amount): Generator
    {
        if ($amount <= 0) throw new InvalidEconomyAmount();

        [$senderName, $senderUuid] = $this->extractPlayerData($from);
        [$receiverName, $receiverUuid] = $this->extractPlayerData($to);

        $senderKey = $this->normalizeKey($from);
        $receiverKey = $this->normalizeKey($to);

        $event = new TransferBalanceEvent($senderName, $receiverName, $senderUuid, $receiverUuid, $amount);

        yield from $this->handleEvent($event, function(?TransferBalanceEvent $ev) use ($senderKey, $receiverKey) {
            if($ev){
                yield from $this->main->getDatabaseManager()
                    ->getEconomyRepository()
                    ->transfer(new TransferEconomyPayload(
                        $ev->getSenderUuid(),
                        $ev->getSenderUsername(),
                        $ev->getReceiverUuid(),
                        $ev->getReceiverUsername(),
                        $ev->getAmount()
                    ));

                $this->modifyCache($senderKey, -$ev->getAmount());
                $this->modifyCache($receiverKey, $ev->getAmount());
                $this->sort();
            }
        });
    }

    /**
     * @throws InvalidEconomyAmount|Throwable
     */
    public function set(Player|string $player, int $amount): Generator
    {
        if ($amount < 0) throw new InvalidEconomyAmount();

        [$username, $uuid] = $this->extractPlayerData($player);
        $key = $this->normalizeKey($player);

        $event = new SetBalanceEvent($username, $uuid, $amount);

        yield from $this->handleEvent($event, function(?SetBalanceEvent $ev) use ($key) {
            if($ev){
                yield from $this->main->getDatabaseManager()
                    ->getEconomyRepository()
                    ->set(new SetEconomyPayload($ev->getAmount(), $ev->getUsername()));

                $this->updateCache($key, $ev->getAmount());
                $this->sort();
            }
        });
    }

    /**
     * @return Generator<EconomyEntry[]>
     */
    public function getTop(int $limit = 10, int $offset = 0): Generator
    {
        return yield from $this->main->getDatabaseManager()
            ->getEconomyRepository()
            ->top(new TopEconomyPayload($limit, $offset));
    }

    private function initCurrency(): void
    {
        $this->main->saveResource('economy.yml');
        $config = new Config($this->main->getDataFolder() . 'economy.yml', Config::YAML);
        $currencyConfig = $config->get('currency');

        $this->currency = new Currency(
            name: $currencyConfig['name'] ?? 'United States Dollar',
            code: $currencyConfig['code'] ?? 'USD',
            symbol: $currencyConfig['symbol'] ?? '$',
            format: $currencyConfig['formatter'] ?? 'compact',
            defaultAmount: $currencyConfig['default']['amount'] ?? 0,
            defaultDecimals: $currencyConfig['default']['decimals'] ?? 0,
            decimals: $currencyConfig['decimals'] ?? true
        );
    }

    /**
     * @template T of EconomyEvent
     * @param EconomyEvent $event
     * @param Closure(T): Generator $action
     * @return Generator
     * @throws Throwable
     */
    private function handleEvent(EconomyEvent $event, Closure $action): Generator {
        $event->call();

        if ($event->isCancelled()) {
            return yield from Await::all([]);
        }

        try {
            return yield from $action($event);
        } catch (Throwable $e) {
            $event->cancel();
            throw $e;
        }
    }

    private function updateCache(string $key, int $newAmount): void
    {
        if (isset($this->cache[$key])) {
            $this->cache[$key]->amount = $newAmount;
        }
        $this->dirty = true;
    }

    private function modifyCache(string $key, int $delta): void
    {
        if (isset($this->cache[$key])) {
            $this->cache[$key]->amount += $delta;
            $this->dirty = true;
        }
    }

    private function sort(): void
    {
        if (! $this->dirty) return;

        uasort($this->cache, static fn(EconomyEntry $a, EconomyEntry $b) => $b->amount <=> $a->amount);

        $position = 1;
        foreach ($this->cache as $entry) {
            $entry->position = $position++;
        }

        $this->dirty = false;
    }

    private function normalizeKey(string|Player $player): string
    {
        return strtolower($player instanceof Player ? $player->getName() : $player);
    }

    private function extractPlayerData(string|Player $player): array
    {
        if ($player instanceof Player) {
            return [strtolower($player->getName()), $player->getUniqueId()->toString()];
        }

        $exact = $this->main->getServer()->getPlayerExact($player);
        return $exact instanceof Player
            ? [strtolower($exact->getName()), $exact->getUniqueId()->toString()]
            : [strtolower($player), $player];
    }

    public function isInCache(string|Player $player): bool
    {
        return isset($this->cache[$this->normalizeKey($player)]);
    }

    public function clearCache(): void
    {
        $this->cache = [];
        $this->dirty = false;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}