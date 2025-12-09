<?php

namespace App\Repositories\Swoole;

use App\Models\Spell\Spell;
use App\Repositories\Database\SpellRepository;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use Illuminate\Support\Collection;

class SpellRepositorySwoole extends SpellRepository implements SpellRepositorySwooleInterface
{
    private Collection $allSpellsById;

    public function __construct()
    {
        parent::__construct();

        $this->allSpellsById = collect();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function findAllById(Collection $spellIds): Collection
    {
        if ($this->allSpellsById->isEmpty()) {
            $this->allSpellsById = Spell::all()->keyBy('id');
        }

        $result = collect();

        foreach ($spellIds as $spellId) {
            if ($this->allSpellsById->has($spellId)) {
                $result->put($spellId, clone $this->allSpellsById->get($spellId));
            }
        }

        return $result;
    }
}
