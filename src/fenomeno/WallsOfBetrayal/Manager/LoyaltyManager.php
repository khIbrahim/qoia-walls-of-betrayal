<?php

namespace fenomeno\WallsOfBetrayal\Manager;

use fenomeno\WallsOfBetrayal\Class\Player\PlayerLoyalty;
use fenomeno\WallsOfBetrayal\Config\HealConfig;
use fenomeno\WallsOfBetrayal\Database\Payload\Loyalty\UpdatePlayerLoyaltyPayload;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyCause;
use fenomeno\WallsOfBetrayal\Enum\Loyalty\LoyaltyRank;
use fenomeno\WallsOfBetrayal\Events\LoyaltyChangeEvent;
use fenomeno\WallsOfBetrayal\Exceptions\RecordNotFoundException;
use fenomeno\WallsOfBetrayal\Game\Loyalty\AFK\PlayerActivityTracker;
use fenomeno\WallsOfBetrayal\libs\SOFe\AwaitGenerator\Await;
use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Sessions\Session;
use fenomeno\WallsOfBetrayal\Utils\Messages\ExtraTags;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesIds;
use fenomeno\WallsOfBetrayal\Utils\Messages\MessagesUtils;
use fenomeno\WallsOfBetrayal\Utils\Utils;
use Generator;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use Symfony\Component\Filesystem\Path;
use Throwable;

class LoyaltyManager
{

    protected const CONFIG_FILE = 'loyalty.yml';

    /** @var array<string, array> cause config from loyalty.yml */
    private array $causes = [];
    private Config $config;

    private readonly PlayerActivityTracker $tracker;

    /** Comptage quotidien par cause (anti-spam), reset quotidien. */
    private array $dailyCauseCounts = []; // [playerName][cause] => int
    private array $dailyDonationDelta = []; // [playerName] => int
    private int $dailyEpoch = 0;

    public function __construct(private readonly Main $main){
        $this->main->saveResource(self::CONFIG_FILE, true);
        $filePath     = Path::join($main->getDataFolder(), self::CONFIG_FILE);
        $this->config = new Config($filePath, Config::YAML);

        foreach ($this->config->get('loyalty_causes') as $value => $loyaltyData){
            if (! LoyaltyCause::tryFrom($value)){
                continue;
            }

            $this->causes[$value] = $loyaltyData;
        }

        $this->tracker = new PlayerActivityTracker($this->main, $this);
    }

    public function grantDonationLoyalty(Player $player, int $amount, string $donationType): void
    {
        if ($amount <= 0) {
            return;
        }

        $baseDelta = $this->getCauseDelta(LoyaltyCause::DONATION);
        if ($baseDelta === 0) {
            $baseDelta = 1;
        }

        $multi = $this->calculateContributionBonus($amount, $donationType);

        $dailyCap    = (int) ($this->config->getNested('parameters.caps.daily.donation_delta', 150));
        $playerName  = $player->getName();
        $today       = $this->ensureDailyWindow();
        $gainedToday = $this->dailyDonationDelta[$today][$playerName] ?? 0;

        $rawDelta = (int) round($baseDelta * $multi);

        $count    = $this->getDailyCauseCount($player, LoyaltyCause::DONATION);
        $diminish = $this->diminishingFactor($count);
        $rawDelta = (int) round($rawDelta * $diminish);

        $room = max(0, $dailyCap - $gainedToday);
        if ($room <= 0) {
            MessagesUtils::sendTo($player, MessagesIds::KINGDOM_LOYALTY_SCORE_INCREASED, [
                ExtraTags::CHANGE => "§e0",
                ExtraTags::REASON => "Donations cap reached",
                ExtraTags::SCORE  => 0
            ]);
            return;
        }

        $applied = min($rawDelta, $room);
        if ($applied <= 0) {
            return;
        }

        $this->addLoyalty($player, LoyaltyCause::DONATION, bonus: (float) ($applied / max(1, $baseDelta)));
        $this->dailyDonationDelta[$today][$playerName] = $gainedToday + $applied;
        $this->incrementDailyCauseCount($player, LoyaltyCause::DONATION);
    }

    public function addLoyalty(Player $player, LoyaltyCause $cause, float $bonus = 1.0): void
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return;
        }

        $loyalty = $session->getLoyalty();
        if(! $loyalty){
            return;
        }

        $this->applyPassiveDecay($loyalty->score);

        $oldScore = $loyalty->score;
        $oldRank  = $loyalty->getLoyaltyRank();

        $base = $this->getCauseDelta($cause);

        $delta = (int) round($base * $bonus);
        if ($base > 0) {
            $count    = $this->getDailyCauseCount($player, $cause);
            $diminish = $this->diminishingFactor($count);
            $delta    = (int) round($delta * $diminish);

            $perCauseCap = (int) ($this->config->getNested("parameters.caps.daily.by_cause.$cause->value", 0));
            if ($perCauseCap > 0) {
                $today = $this->ensureDailyWindow();
                $pname = $player->getName();
                $earned = $this->dailyCauseCounts[$today][$pname]['earned'][$cause->value] ?? 0;
                $room = max(0, $perCauseCap - $earned);
                if ($room <= 0) {
                    $delta = 0;
                } else {
                    $delta = min($delta, $room);
                }
                $this->dailyCauseCounts[$today][$pname]['earned'][$cause->value] = $earned + max(0, $delta);
            }
        }

        if ($delta === 0) {
            return;
        }

        $newScore = $oldScore + $delta;

        $event = new LoyaltyChangeEvent($player, $oldScore, $newScore, $cause);
        $event->call();
        if ($event->isCancelled()){
            return;
        }

        $score = $event->getNewScore();

        $loyalty->score = $score;
        $session->setDirty();

        Await::g2c(
            $this->main->getDatabaseManager()
                ->getPlayerLoyaltyRepository()
                ->updateOrInsertLoyalty(new UpdatePlayerLoyaltyPayload(
                    $loyalty->uuid,
                    $loyalty->username,
                    $loyalty->kingdomId,
                    $loyalty->score,
                    $loyalty->contributionCount,
                    $loyalty->betrayalCount,
                    $loyalty->lastBetrayal
                )),
            function() use ($player, $oldRank, $loyalty, $delta, $cause) {
                $this->handleRankChange($player, $oldRank, $loyalty->getLoyaltyRank(), $delta, $cause);
            },
            fn($e) => Utils::onFailure($e, $player, "Failed to update loyalty score $loyalty->score for {$player->getName()}")
        );

        if ($base > 0) {
            $this->incrementDailyCauseCount($player, $cause);
        }
    }

    private function handleRankChange(Player $player, LoyaltyRank $oldRank, LoyaltyRank $newRank, int $score, LoyaltyCause $cause): void
    {
        if($oldRank === $newRank){
            if($score !== 0){
                $this->sendLoyaltyFeedback($player, $cause, $score);
            }
            return;
        }

        $this->sendRankChangeMessage($player, $newRank);
        $this->playRankChangeEffects($player, $newRank);
    }

    private function sendLoyaltyFeedback(Player $player, LoyaltyCause $cause, int $score): void
    {
        $prefix = $score > 0 ? "§a+" : "§c";
        $reason = $this->getCauseDisplayName($cause);

        MessagesUtils::sendTo($player, MessagesIds::KINGDOM_LOYALTY_SCORE_INCREASED, [
            ExtraTags::CHANGE => $prefix . abs($this->getCauseDelta($cause)),
            ExtraTags::REASON => $reason,
            ExtraTags::SCORE  => $score
        ]);
    }

    public function sendRankChangeMessage(Player $player, LoyaltyRank $newRank): void
    {
        $session = Session::get($player);
        $kingdom = $session->getKingdom();

        if ($newRank === LoyaltyRank::TRAITOR){
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_LOW, [
                ExtraTags::KINGDOM => $kingdom ? $kingdom->getDisplayName() : "N/A"
            ]);
        } elseif($newRank === LoyaltyRank::SUSPECT){
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_MEDIUM, [
                ExtraTags::KINGDOM => $kingdom ? $kingdom->getDisplayName() : "N/A"
            ]);
        } elseif($newRank === LoyaltyRank::LOYAL){
            MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_HIGH, [
                ExtraTags::KINGDOM => $kingdom ? $kingdom->getDisplayName() : "N/A"
            ]);
        }
    }

    private function playRankChangeEffects(Player $player, LoyaltyRank $newRank): void
    {
        switch($newRank) {
            case LoyaltyRank::TRAITOR:
                MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_BETRAYAL_MODE_ENABLED);
                $player->getWorld()->addSound($player->getLocation(), new NoteSound(NoteInstrument::DOUBLE_BASS(), 1));
            break;
            case LoyaltyRank::LOYAL:
                MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_LOYAL_MODE_ENABLED);
                $player->getWorld()->addSound($player->getLocation(), new NoteSound(NoteInstrument::CHIME(), 20));
            break;
            case LoyaltyRank::SUSPECT:
                MessagesUtils::sendTo($player, MessagesIds::KINGDOMS_LOYALTY_SUSPECT_MODE_ENABLED);
                $player->getWorld()->addSound($player->getLocation(), new ClickSound());
            break;
            default:
        }
    }

    public function getCauseDisplayName(LoyaltyCause $cause)
    {
        return $this->causes[$cause->value]['name'] ?? $cause->value;
    }

    public function getCauseDescription(LoyaltyCause $cause): string
    {
        return $this->causes[$cause->value]['description'] ?? '';
    }

    public function getCauseDelta(LoyaltyCause $cause): int
    {
        return $this->causes[$cause->value]['delta'] ?? $cause->defaultDelta();
    }

    public function getCauseDisplayItem(LoyaltyCause $cause): Item
    {
        $raw = $this->causes[$cause->value]['displayItem'] ?? $this->causes[$cause->value]['item'] ?? 'paper';
        return StringToItemParser::getInstance()->parse($raw) ?? VanillaItems::PAPER();
    }

    public function canBetray(Player $damager): bool
    {
        $session = Session::get($damager);
        if(! $session->isLoaded()){
            return false;
        }

        $loyalty = $session->getLoyalty();
        if(! $loyalty){
            return false;
        }

        return $loyalty->getLoyaltyRank()->canBetray();
    }

    public function getLoyaltyPercentage(Player $player): float|int
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return 50;
        }

        $loyalty = $session->getLoyalty();
        $score = $loyalty?->score ?? 0;

        $scale = (float) $this->config->getNested('parameters.ui.scale', 3000.0);
        if ($score <= 0) {
            return 0;
        }
        $pct = 100.0 * (1.0 - exp(-$score / max(1.0, $scale)));
        return (int) round(min(100.0, $pct));
    }

    public function getLoyaltyRank(Player $player): ?LoyaltyRank
    {
        $session = Session::get($player);
        if(! $session->isLoaded()){
            return null;
        }

        $loyalty = $session->getLoyalty();
        return $loyalty?->getLoyaltyRank();
    }

    public function getTracker(): PlayerActivityTracker
    {
        return $this->tracker;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function calculateContributionBonus(int $amount, string $type): float
    {
        $amount = max(0, $amount);
        $pathBase    = "parameters.bonus.donation";
        $typeNode    = $this->config->getNested("$pathBase.$type");
        $defaultNode = $this->config->getNested("$pathBase._default");

        $cfg = [
            'base'  => 1.0,
            'unit'  => 100,
            'scale' => 0.75,
            'max'   => 20.0,
            'tiers' => [
                100     => 1.25,
                500     => 1.5,
                1000    => 2.0,
                5000    => 4.0,
                10000   => 7.0,
                50000   => 10.0,
                100000  => 12.0,
            ],
        ];

        $mergeNode = function($node) use (&$cfg) {
            if (is_array($node)) {
                foreach (['base','unit','scale','max'] as $k) {
                    if (isset($node[$k]) && is_numeric($node[$k])) {
                        $cfg[$k] = +$node[$k];
                    }
                }
                if (isset($node['tiers']) && is_array($node['tiers'])) {
                    $tiers = [];
                    foreach ($node['tiers'] as $threshold => $mult) {
                        if (is_numeric($threshold) && is_numeric($mult)) {
                            $tiers[(int)$threshold] = (float)$mult;
                        }
                    }
                    if ($tiers !== []) {
                        ksort($tiers);
                        $cfg['tiers'] = $tiers;
                    }
                }
            } elseif (is_numeric($node)) {
                $cfg['base'] = (float)$node;
            }
        };

        $mergeNode($defaultNode);
        $mergeNode($typeNode);

        $tierMult = (float)$cfg['base'];
        foreach ($cfg['tiers'] as $threshold => $mult) {
            if ($amount >= $threshold) {
                $tierMult = max($tierMult, (float)$mult);
            } else {
                break;
            }
        }

        $unit       = max(1, (int)$cfg['unit']);
        $normalized = $amount / $unit;
        $extra      = log(1 + $normalized, 10) * (float)$cfg['scale'];

        $result = $tierMult + $extra;
        $result = min($result, (float)$cfg['max']);
        if (!is_finite($result) || $result < 1.0) {
            $result = 1.0;
        }

        $globalMax = (float) $this->config->getNested('parameters.bonus.max', 2.0);
        $result = min($result, $globalMax);

        return round($result, 2);
    }

    private function applyPassiveDecay(int &$score): void
    {
        $halfLifeHours = (float) $this->config->getNested('parameters.decay.half_life_hours', 72.0);
        if ($halfLifeHours <= 0) {
            return;
        }
        $lambda = log(2.0) / $halfLifeHours;
        if ($score > 0) {
            $score = (int) round($score * exp(-$lambda));
        } elseif ($score < 0) {
            $score = (int) round($score * exp(-$lambda));
        }
    }

    private function ensureDailyWindow(): int
    {
        $today = (int) floor(time() / 86400);
        if ($today !== $this->dailyEpoch) {
            $this->dailyEpoch = $today;
            $this->dailyCauseCounts = [];
            $this->dailyDonationDelta = [];
        }
        if (! isset($this->dailyCauseCounts[$today])) {
            $this->dailyCauseCounts[$today] = [];
        }
        if (! isset($this->dailyDonationDelta[$today])) {
            $this->dailyDonationDelta[$today] = [];
        }
        return $today;
    }

    public function getDailyCauseCount(Player $player, LoyaltyCause $cause): int
    {
        $today = $this->ensureDailyWindow();
        $pname = $player->getName();
        return $this->dailyCauseCounts[$today][$pname]['count'][$cause->value] ?? 0;
    }

    private function incrementDailyCauseCount(Player $player, LoyaltyCause $cause): void
    {
        $today = $this->ensureDailyWindow();
        $pname = $player->getName();
        $this->dailyCauseCounts[$today][$pname]['count'][$cause->value]
            = ($this->dailyCauseCounts[$today][$pname]['count'][$cause->value] ?? 0) + 1;
    }

    public function diminishingFactor(int $countAlreadyToday): float
    {
        $k = (float) $this->config->getNested('parameters.caps.diminish.k', 0.4);
        return 1.0 / (1.0 + max(0.0, $k * $countAlreadyToday));
    }

    /**
     * @throws RecordNotFoundException
     * @return Generator<PlayerLoyalty>
     */
    public function getPlayerLoyalty(string $playerName): Generator
    {
        $player = $this->main->getServer()->getPlayerExact($playerName);
        if ($player instanceof Player) {
            $session = Session::get($player);
            if ($session->isLoaded()) {
                $loyalty = $session->getLoyalty();
                if ($loyalty !== null) {
                    return $loyalty;
                }
            }
        }

        return yield from $this->main->getDatabaseManager()
            ->getPlayerLoyaltyRepository()
            ->getLoyaltyByName($playerName);
    }

    public function getDailyEarnedFor(Player $healer, LoyaltyCause $cause)
    {
        $today = $this->ensureDailyWindow();
        $pname = $healer->getName();
        return $this->dailyCauseCounts[$today][$pname]['earned'][$cause->value] ?? 0;
    }

    public function grantHealLoyalty(Player $healer, Player $target, int $healAmount, int $beforeHp): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($healer, $target, $healAmount, $beforeHp){
            try {
                $base = HealConfig::getBaseDelta();

                if ($beforeHp <= (HealConfig::getVeryLowHpThreshold() ?? 6)) {
                    $severityFactor = 2.5;
                } elseif ($beforeHp <= (HealConfig::getLowHpThreshold() ?? 10)) {
                    $severityFactor = 1.75;
                } elseif ($beforeHp <= (HealConfig::getMediumHpThreshold() ?? 14)) {
                    $severityFactor = 1.25;
                } else {
                    $severityFactor = 1.0;
                }

                $itemMultiplier = 2.5;

                $count = $this->getDailyCauseCount($healer, LoyaltyCause::HEAL_ALLY);
                $diminish = $this->diminishingFactor($count);

                $rawDelta = (int)round($base * $severityFactor * $itemMultiplier * $diminish);

                $dailyCap = HealConfig::getDailyCapDelta() ?? 200;
                $gainedToday = $this->getDailyEarnedFor($healer, LoyaltyCause::HEAL_ALLY) ?? 0;
                $room = max(0, $dailyCap - $gainedToday);
                $appliedDelta = min($rawDelta, $room);

                if ($appliedDelta > 0) {
                    $this->addLoyalty($healer, LoyaltyCause::HEAL_ALLY, $appliedDelta);

                    if ($appliedDelta >= (HealConfig::getHeroBroadcastThreshold() ?? 10)) {
                        $kingdom = Session::get($healer)->getKingdom();
                        $kingdom?->broadcastMessage(MessagesIds::HERO_HEALED_ALLY, [
                            ExtraTags::PLAYER => $healer->getName(),
                            ExtraTags::TARGET => $target->getName(),
                            ExtraTags::HEAL => $healAmount,
                        ]);
                    }
                }

                $resolve();
            } catch (Throwable) {
                $reject();
            }
        });
    }

}