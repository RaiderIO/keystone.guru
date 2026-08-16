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

    /** @var int|null `spells` row count when the catalogs were built - a mismatch means another process inserted or deleted a spell */
    private ?int $catalogSpellCount = null;

    /** @var int|null Highest `spell_dungeons` id when the catalogs were built - `spells` and `spell_dungeons` carry no timestamps, so the auto-increment watermark is the cheapest insert detector */
    private ?int $catalogMaxSpellDungeonId = null;

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
            $this->spellsWithCharacteristics = parent::getAllWithCharacteristic();
            $this->stampCatalogs();
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
            $this->allSpellsKeyedWithSpellDungeons = parent::getAllKeyedWithSpellDungeons();
            $this->stampCatalogs();
        }

        return $this->allSpellsKeyedWithSpellDungeons;
    }

    /**
     * Drops both memoized catalogs when the stamps say the tables changed (or the TTL ran out). Two cheap
     * aggregate queries per call - callers hit this once per extraction job, against the ~160 ms a full
     * rebuild costs.
     */
    private function invalidateCatalogsIfStale(): void
    {
        if ($this->allSpellsKeyedWithSpellDungeons === null && $this->spellsWithCharacteristics === null) {
            return;
        }

        $stale = $this->catalogBuiltAtTimestamp === null
            || Carbon::now()->getTimestamp() - $this->catalogBuiltAtTimestamp >= self::CATALOG_TTL_SECONDS
            || $this->catalogSpellCount !== Spell::query()->count()
            || $this->catalogMaxSpellDungeonId !== (int)SpellDungeon::query()->max('id');

        if ($stale) {
            $this->allSpellsKeyedWithSpellDungeons = null;
            $this->spellsWithCharacteristics       = null;
        }
    }

    private function stampCatalogs(): void
    {
        $this->catalogSpellCount        = Spell::query()->count();
        $this->catalogMaxSpellDungeonId = (int)SpellDungeon::query()->max('id');
        $this->catalogBuiltAtTimestamp  = Carbon::now()->getTimestamp();
    }
}
