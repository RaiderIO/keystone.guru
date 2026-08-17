<?php

namespace App\Repositories\Swoole;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Repositories\Database\SpellRepository;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Override;

class SpellRepositorySwoole extends SpellRepository implements SpellRepositorySwooleInterface
{
    /**
     * @var int Rebuild the memoized catalogs after this many seconds even when no insert was detected, so
     *          in-place updates made by other processes (characteristic assignments from the admin panel,
     *          schools_mask repairs by other workers) are picked up without a worker restart.
     */
    private const int CATALOG_TTL_SECONDS = 300;

    /**
     * @var Collection<int, Spell>
     */
    private Collection $allSpellsById;

    /**
     * @var Collection<int, Spell>|null
     */
    private ?Collection $spellsWithCharacteristics = null;

    /**
     * The full spell catalog for the combat log extraction pipeline (#4058). Deliberately handed out by
     * reference and NOT cloned (unlike findAllById): the extraction collectors put() newly created spells
     * into it and mutate the models they repair, which is exactly how a long-lived worker's catalog stays
     * current with its own writes between the stamp-based rebuilds.
     *
     * @var Collection<int, Spell>|null
     */
    private ?Collection $allSpellsKeyedWithSpellDungeons = null;

    /** @var int|null `spells` row count just before the catalogs were built - `spells` and `spell_dungeons` carry no timestamps, so row counts are the change detector. Only consulted while the full catalog is absent; see invalidateCatalogsIfStale() */
    private ?int $catalogSpellCount = null;

    /** @var int|null `spell_dungeons` row count just before the catalogs were built */
    private ?int $catalogSpellDungeonCount = null;

    /** @var int|null Unix timestamp of the last catalog build, for the TTL fallback */
    private ?int $catalogBuiltAtTimestamp = null;

    public function __construct()
    {
        parent::__construct();

        $this->allSpellsById = collect();
    }

    /**
     * @inheritDoc
     * @param  Collection<int, int>|Collection<int, Spell> $spellIds
     * @return Collection<int, Spell>
     */
    #[Override]
    public function findAllById(Collection $spellIds): Collection
    {
        if ($spellIds->isEmpty()) {
            return collect();
        }

        $spellIds     = $spellIds->unique();
        $spellIdsById = $spellIds->flip();
        $cachedSpells = $this->allSpellsById->intersectByKeys($spellIdsById);

        if ($cachedSpells->count() !== $spellIds->count()) {
            $missingSpellIds = $spellIdsById->diffKeys($cachedSpells)->keys();

            $this->allSpellsById = $this->allSpellsById->merge(
                Spell::query()->whereIn('id', $missingSpellIds)->get()->keyBy('id'),
            );
        }

        $result = collect();

        foreach ($spellIds as $spellId) {
            // Sometimes the spell may still not be found so ensure to guard against it.
            if ($this->allSpellsById->has($spellId)) {
                $result->put($spellId, clone $this->allSpellsById->get($spellId));
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @return Collection<int, Spell>
     */
    #[Override]
    public function getAllWithCharacteristic(): Collection
    {
        $this->invalidateCatalogsIfStale();

        if ($this->spellsWithCharacteristics === null) {
            // Stamp BEFORE the build: a row another process inserts while the query runs then mismatches the
            // next check and forces a rebuild - over-invalidation, never silent staleness
            $this->stampCatalogs();
            $this->spellsWithCharacteristics = parent::getAllWithCharacteristic();
        }

        return $this->spellsWithCharacteristics;
    }

    /**
     * @inheritDoc
     * @return Collection<int, Spell>
     */
    #[Override]
    public function getAllKeyedWithSpellDungeons(): Collection
    {
        $this->invalidateCatalogsIfStale();

        if ($this->allSpellsKeyedWithSpellDungeons === null) {
            // Stamp BEFORE the build - see getAllWithCharacteristic(). For the full catalog the stored
            // stamps are only a fallback anyway: once it exists, staleness compares against the live catalog
            $this->stampCatalogs();
            $this->allSpellsKeyedWithSpellDungeons = parent::getAllKeyedWithSpellDungeons();
        }

        return $this->allSpellsKeyedWithSpellDungeons;
    }

    /**
     * Drops both memoized catalogs when the database no longer matches them (or the TTL ran out). Two cheap
     * aggregate queries per call - callers hit this once per extraction job, against the ~160 ms a full
     * rebuild costs.
     *
     * When the full catalog exists, the expected counts are derived from the catalog itself rather than the
     * stored stamps: the extraction collectors keep the shared catalog current with this worker's own writes
     * (put() on spell creation, spellDungeons relation reloads), so a self-write matches the database and
     * does NOT force a pointless full rebuild on the next job - only writes from OTHER processes mismatch.
     */
    private function invalidateCatalogsIfStale(): void
    {
        if ($this->allSpellsKeyedWithSpellDungeons === null && $this->spellsWithCharacteristics === null) {
            return;
        }

        if ($this->allSpellsKeyedWithSpellDungeons !== null) {
            $expectedSpellCount        = $this->allSpellsKeyedWithSpellDungeons->count();
            $expectedSpellDungeonCount = (int)$this->allSpellsKeyedWithSpellDungeons->sum(
                static fn(Spell $spell) => $spell->spellDungeons->count(),
            );
        } else {
            $expectedSpellCount        = $this->catalogSpellCount;
            $expectedSpellDungeonCount = $this->catalogSpellDungeonCount;
        }

        $stale = $this->catalogBuiltAtTimestamp === null
            || Carbon::now()->getTimestamp() - $this->catalogBuiltAtTimestamp >= self::CATALOG_TTL_SECONDS
            || $expectedSpellCount !== Spell::query()->count()
            || $expectedSpellDungeonCount !== SpellDungeon::query()->count();

        if ($stale) {
            $this->allSpellsKeyedWithSpellDungeons = null;
            $this->spellsWithCharacteristics       = null;
        }
    }

    private function stampCatalogs(): void
    {
        $this->catalogSpellCount        = Spell::query()->count();
        $this->catalogSpellDungeonCount = SpellDungeon::query()->count();
        $this->catalogBuiltAtTimestamp  = Carbon::now()->getTimestamp();
    }
}
