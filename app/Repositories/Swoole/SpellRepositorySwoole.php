<?php

namespace App\Repositories\Swoole;

use App\Models\Spell\Spell;
use App\Repositories\Database\SpellRepository;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use Illuminate\Support\Collection;
use Override;

class SpellRepositorySwoole extends SpellRepository implements SpellRepositorySwooleInterface
{
    private Collection $allSpellsById;

    private ?Collection $spellsWithCharacteristics = null;

    public function __construct()
    {
        parent::__construct();

        $this->allSpellsById = collect();
    }

    /**
     * @inheritDoc
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

    #[Override]
    public function getAllWithCharacteristic(): Collection
    {
        if ($this->spellsWithCharacteristics === null) {
            $this->spellsWithCharacteristics = parent::getAllWithCharacteristic();
        }

        return $this->spellsWithCharacteristics;
    }
}
