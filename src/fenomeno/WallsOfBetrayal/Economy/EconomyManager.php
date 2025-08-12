<?php

namespace fenomeno\WallsOfBetrayal\Economy;

use fenomeno\WallsOfBetrayal\Cache\EconomyEntry;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\AddEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\GetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\InsertEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SetEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\SubtractEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TopEconomyPayload;
use fenomeno\WallsOfBetrayal\Database\Payload\Economy\TransferEconomyPayload;
use fenomeno\WallsOfBetrayal\Economy\Currency\Currency;
use fenomeno\WallsOfBetrayal\Exceptions\DatabaseException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordAlreadyExistsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordMissingDatException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\EconomyRecordNotFoundException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InsufficientFundsException;
use fenomeno\WallsOfBetrayal\Exceptions\Economy\InvalidEconomyAmount;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class EconomyManager
{

    private Currency $currency;
    private Config $config;

    /** @var EconomyEntry[] */
    private array $cached = [];

    public function __construct(private readonly Main $main){
        $this->main->saveResource('economy.yml');
        $this->config = new Config($this->main->getDataFolder() . 'economy.yml', Config::YAML);

        $this->initCurrency();
    }

    private function initCurrency(): void
    {
        $currencyConfig = $this->config->get('currency');

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
     * @throws EconomyRecordNotFoundException
     * @throws EconomyRecordMissingDatException
     * @throws DatabaseException
     * @return Generator<EconomyEntry>
     */
    public function get(Player|string $name, ?string $uuid = null): Generator
    {
        if($name instanceof Player){
            $name = strtolower($name->getName());
        }

        if (isset($this->cached[$name])) {
            return $this->cached[$name];
        }

        $entry = yield from $this->main->getDatabaseManager()
                ->getEconomyRepository()
                ->get(new GetEconomyPayload($name, $uuid));

        $this->cached[$name] = $entry;

        return $entry;
    }

    public function insert(string $name, string $uuid, ?\Closure $onSuccess = null, ?\Closure $onFailure = null): void {
        $name = strtolower($name);
        $onSuccess ??= static function(EconomyEntry $e): void {};
        $onFailure ??= static function(\Throwable $e): void { throw $e; };

        try {
            Await::g2c(
                $this->main->getDatabaseManager()->getEconomyRepository()->insert(new InsertEconomyPayload($name, $uuid)),
                function () use ($uuid, $name, $onSuccess): void {
                    $entry = new EconomyEntry($name, $uuid, 0);
                    $this->cached[$entry->username] = $entry;
                    $this->sort();
                    $onSuccess($entry);
                },
                $onFailure
            );
        } catch (DatabaseException $e) {
            $this->main->getLogger()->info("ECONOMY - Failed to insert name: $name, uuid: $uuid: " . $e->getPrevious()->getMessage());
            $this->main->getLogger()->logException($e);
        } catch (EconomyRecordAlreadyExistsException $e) {
            $this->main->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * @throws InvalidEconomyAmount
     * @throws EconomyRecordNotFoundException|DatabaseException
     */
    public function add(string|Player $player, int $amount): Generator {
        if ($amount <= 0) {
            throw new InvalidEconomyAmount();
        }

        [$username, $uuid] = $this->payloadPlayerData($player);

        yield from $this->main->getDatabaseManager()
            ->getEconomyRepository()
            ->add(new AddEconomyPayload(amount: $amount, username: $username, uuid: $uuid));

        $this->cached[$username]->amount += $amount;

        $this->sort();
    }

    /**
     * @throws InvalidEconomyAmount
     * @throws EconomyRecordNotFoundException|InsufficientFundsException
     */
    public function subtract(Player|string $player, int $amount): Generator
    {
        if ($amount <= 0) {
            throw new InvalidEconomyAmount();
        }

        [$username, $uuid] = $this->payloadPlayerData($player);

        yield from $this->main->getDatabaseManager()
            ->getEconomyRepository()
            ->subtract(new SubtractEconomyPayload(amount: $amount, username: $username, uuid: $uuid));

        $this->cached[$username]->amount -= $amount;

        $this->sort();
    }

    public function sort(): void
    {
        uasort($this->cached, static fn (EconomyEntry $a, EconomyEntry $b) => $b->amount <=> $a->amount);

        $i = 1;

        foreach ($this->cached as $key => $entry) {
            $this->cached[$key]->position = $i;
            $i++;
        }
    }

    /**
     * @throws InvalidEconomyAmount
     * @throws EconomyRecordNotFoundException
     * @throws InsufficientFundsException
     * @throws EconomyRecordMissingDatException
     * @throws DatabaseException
     */
    public function transfer(string|Player $from, string|Player $to, int $amount): \Generator {
        if ($amount <= 0) throw new InvalidEconomyAmount();

        [$sName, $sUuid] = $this->payloadPlayerData($from);
        [$rName, $rUuid] = $this->payloadPlayerData($to);

        if (! isset($this->cached[$sName])) {
            try { yield from $this->get($sName, $sUuid);} catch (EconomyRecordNotFoundException){$this->insert($sName, $sUuid, fn() => yield from $this->add($from, $amount));}
        }
        if (! isset($this->cached[$rName])) {
            try { yield from $this->get($rName, $rUuid);} catch (EconomyRecordNotFoundException){$this->insert($rName, $rUuid, fn() => yield from $this->add($to, $amount));}
        }

        yield from $this->main->getDatabaseManager()->getEconomyRepository()->transfer(
            new TransferEconomyPayload($sUuid, $rUuid, $amount)
        );

        $this->cached[$sName]->amount -= $amount;
        $this->cached[$rName]->amount += $amount;
        $this->sort();
    }

    /**
     * @throws InvalidEconomyAmount
     */
    public function set(Player|string $player, int $amount): Generator
    {
        if ($amount < 0) {
            throw new InvalidEconomyAmount();
        }

        [$username, $uuid] = $this->payloadPlayerData($player);

        yield from $this->main->getDatabaseManager()
            ->getEconomyRepository()
            ->set(new SetEconomyPayload($amount, $uuid, $username));

        $this->cached[$username]->amount = $amount;
        $this->sort();
    }

    /**
     * Récupère le leaderboard paginé (et met à jour le cache local pour ces entrées).
     *
     * @return Generator<EconomyEntry[]>
     */
    public function getTop(int $limit = 10, int $offset = 0): Generator {
        $entries = yield from $this->main->getDatabaseManager()
            ->getEconomyRepository()
            ->top(new TopEconomyPayload($limit, $offset));

        /** @var EconomyEntry $e */
        foreach ($entries as $e) {
            $key = strtolower($e->uuid);
            $this->cached[$key] = $e;
        }

        return $entries;
    }

    private function payloadPlayerData(string|Player $player): array
    {
        if($player instanceof Player){
            return [
                strtolower($player->getName()),
                $player->getUniqueId()->toString()
            ];
        } else {
            $playerExact = $this->main->getServer()->getPlayerExact($player);
            if ($playerExact instanceof Player){
                return [
                    strtolower($playerExact->getName()),
                    $playerExact->getUniqueId()->toString()
                ];
            } else {
                return [$player, $player];
            }
        }
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

}