<?php

namespace App\Service\AffixGroup;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Dungeon;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AffixGroupEaseTierService implements AffixGroupEaseTierServiceInterface
{
    /**
     * @var array<string, string> Keys are KSG names, values are Archon.gg names
     */
    public const array DUNGEON_NAME_MAPPING = [
        "Dawn of the Infinite: Galakrond's Fall" => "Galakrond's Fall",
        "Dawn of the Infinite: Murozond's Rise"  => "Murozond's Rise",
        'The Everbloom'                          => 'Everbloom',
        'Uldaman: Legacy of Tyr'                 => 'Uldaman',

        // TWW S3
        'Ara-Kara, City of Echoes'    => 'Ara-Kara',
        'Halls of Atonement'          => 'Halls',
        'Operation: Floodgate'        => 'Floodgate',
        'Priory of the Sacred Flame'  => 'Priory',
        'Tazavesh: Streets of Wonder' => 'Streets',
        "Tazavesh: So'leah's Gambit"  => 'Gambit',

        // Midnight S1
        "Magisters' Terrace"          => "Magisters'",
        'The Seat of the Triumvirate' => 'Seat',
    ];
    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';

    public function __construct(
        private readonly SeasonServiceInterface                    $seasonService,
        private readonly SeasonAffixGroupServiceInterface          $seasonAffixGroupService,
        private readonly AffixGroupEaseTierServiceLoggingInterface $log,
    ) {
    }

    /**
     * @param array<string, mixed>  $tierList
     * @param array<string, string> $dungeonNameMapping
     */
    public function getTiersHash(array $tierList, array $dungeonNameMapping): string
    {
        $affixes        = $tierList['encounterTierList']['label'];
        $tiersByDungeon = [];
        foreach ($tierList['encounterTierList']['tierLists'][0]['tiers'] as $tier) {
            foreach ($tier['entries'] as $entry) {
                $tiersByDungeon[$dungeonNameMapping[$entry['name']] ?? $entry['name']] = $tier['tier'];
            }
        }

        // Sort by name
        ksort($tiersByDungeon);

        $implode = [];
        foreach ($tiersByDungeon as $dungeon => $tier) {
            $implode[] = sprintf(
                '%s|%s|%s',
                $dungeon,
                $affixes,
                $tier,
            );
        }

        return md5(implode('|', $implode));
    }

    /**
     * {@inheritDoc}
     * @param  array<string, mixed> $tierListsResponse
     * @throws Exception
     */
    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull
    {
        $lastEaseTierPull = AffixGroupEaseTierPull::latest()->first();

        $affixGroupString = $tierListsResponse['encounterTierList']['label'];
        // TWW S1 _kinda_ did away with affixes, but I still have them, Archon removed them
        // so I'm just subbing them with the current affix group if not set
        $currentSeason = $this->seasonService->getCurrentSeason();
        $affixGroup    = $affixGroupString === null
            ? ($currentSeason !== null ? $this->seasonAffixGroupService->getCurrentAffixGroup($currentSeason) : null)
            : $this->getAffixGroupByString($affixGroupString);

        if ($affixGroup === null) {
            $this->log->parseTierListUnknownAffixGroup($affixGroupString);

            return null;
        }

        $result    = null;
        $tiersHash = $this->getTiersHash($tierListsResponse, array_flip(self::DUNGEON_NAME_MAPPING));

        try {
            $lastUpdatedAt = Carbon::createFromFormat(self::DATE_TIME_FORMAT, $tierListsResponse['lastUpdated']);
        } catch (InvalidFormatException $exception) {
            $this->log->parseTierListInvalidLastUpdated($exception, $tierListsResponse['lastUpdated']);

            return null;
        }

        if ($lastEaseTierPull === null ||
            $lastEaseTierPull->created_at->isBefore($lastUpdatedAt) ||
            $lastEaseTierPull->affix_group_id !== $affixGroup->id ||
            $lastEaseTierPull->tiers_hash !== $tiersHash) {
            $affixGroupString       = $affixGroup->text;
            $affixGroupEaseTierPull = AffixGroupEaseTierPull::create([
                'affix_group_id'  => $affixGroup->id,
                'tiers_hash'      => $tiersHash,
                'last_updated_at' => $lastUpdatedAt,
            ]);

            /** @var Collection<string, Dungeon> $dungeonList */
            $dungeonList = Dungeon::active()->get()->keyBy(static function (Dungeon $dungeon): string {
                // Translate the name of the dungeon to English (from a key), and then match it
                $ksgDungeonName = (string)__($dungeon->name, [], 'en_US');

                return self::DUNGEON_NAME_MAPPING[$ksgDungeonName] ?? $ksgDungeonName;
            });

            $affixGroupEaseTiersAttributes = [];
            foreach ($tierListsResponse['encounterTierList']['tierLists'][0]['tiers'] as $tierList) {
                /** @var array{tier: string, entries: array<int, mixed>} $tierList */
                try {
                    $tier = $tierList['tier'];
                    $this->log->parseTierListParseTierStart($affixGroupString, $tier, count($tierList['entries']));

                    foreach ($tierList['entries'] as $dungeon) {
                        /** @var array{id: int, name: string, url: string} $dungeon */
                        // If found
                        $archonDungeonName = $dungeon['name'];
                        /** @var Dungeon|null $dungeon */
                        $dungeon = $dungeonList->get($archonDungeonName);

                        if ($dungeon === null) {
                            $this->log->parseTierListUnknownDungeon($archonDungeonName);

                            continue;
                        }

                        $affixGroupEaseTiersAttributes[] = [
                            'affix_group_ease_tier_pull_id' => $affixGroupEaseTierPull->id,
                            'affix_group_id'                => $affixGroup->id,
                            'dungeon_id'                    => $dungeon->id,
                            'tier'                          => $tier,
                        ];

                        $this->log->parseTierListSavedDungeonTier($archonDungeonName, $tier);
                    }
                } finally {
                    $this->log->parseTierListParseTierEnd();
                }
            }

            $affixGroupEaseTierSaveResult = AffixGroupEaseTier::insert($affixGroupEaseTiersAttributes);
            $this->log->parseTierListSave($affixGroupEaseTierSaveResult);

            $result = $affixGroupEaseTierPull;
        } else {
            $this->log->parseTierListDataNotUpdatedYet();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string
    {
        $result = null;

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();

        if ($latestAffixGroupEaseTierPull !== null) {
            /** @var AffixGroupEaseTier|null $affixGroupEaseTier */
            $affixGroupEaseTier = $latestAffixGroupEaseTierPull->affixGroupEaseTiers()
                ->where('affix_group_id', $affixGroup->id)
                ->where('dungeon_id', $dungeon->id)
                ->first();

            $result = $affixGroupEaseTier?->tier;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @param Collection<int, AffixGroup> $affixGroups
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection
    {
        $result = collect();

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();
        if ($latestAffixGroupEaseTierPull !== null) {
            /** @var Collection<int, AffixGroupEaseTier> $result */
            $result = $latestAffixGroupEaseTierPull->affixGroupEaseTiers()
                ->whereIn('affix_group_id', $affixGroups->pluck('id')->toArray())
                ->get()
                ->groupBy('affix_group_id');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTiers(): Collection
    {
        $result = collect();

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();
        if ($latestAffixGroupEaseTierPull !== null) {
            $result = $latestAffixGroupEaseTierPull->affixGroupEaseTiers;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAffixGroupByString(string $affixString): ?AffixGroup
    {
        $currentSeason = $this->seasonService->getCurrentSeason();
        if ($currentSeason === null) {
            return null;
        }

        /** @var Collection<string, Affix> $affixesByName */
        $affixesByName = Affix::all()->keyBy(static fn(Affix $affix): string => (string)__($affix->name, [], 'en_US'));

        $affixNames = collect(explode(',', $affixString))
            ->map(static fn(string $affixName): string => trim($affixName))
            ->filter(static fn(string $affixName): bool => $affixName !== '');

        // Without any affixes to match on every affix group would be a match - refuse to guess instead
        if ($affixNames->isEmpty()) {
            $this->log->getAffixGroupByStringNoMatchingAffixGroup($affixString);

            return null;
        }

        // Check if there's any affixes in the list that we cannot find in our own database
        $invalidAffixes = $affixNames->reject(static fn(string $affixName): bool => $affixesByName->has($affixName));

        if ($invalidAffixes->isNotEmpty()) {
            $this->log->getAffixGroupByStringUnknownAffixes($invalidAffixes->join(', '));

            return null;
        }

        $affixKeys = $affixNames->map(static function (string $affixName) use ($affixesByName): string {
            /** @var Affix $affix Guaranteed to exist - any name that could not be resolved was rejected above */
            $affix = $affixesByName->get($affixName);

            return $affix->key;
        });

        // The amount of affixes an affix group has varies per season (three in DF S4, four in TWW S3, five in
        // Midnight S1), so match on the affixes an affix group actually has rather than on a hardcoded count.
        // A season may contain multiple affix groups with the exact same set of affixes - they share an ease tier,
        // so returning the first affix group that has all the given affixes is fine.
        foreach ($currentSeason->affixGroups as $affixGroup) {
            if ($affixKeys->every(static fn(string $affixKey): bool => $affixGroup->hasAffix($affixKey))) {
                return $affixGroup;
            }
        }

        $this->log->getAffixGroupByStringNoMatchingAffixGroup($affixString);

        return null;
    }
}
