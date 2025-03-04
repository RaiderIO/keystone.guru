<?php

namespace App\Repositories\Swoole;

use App\Models\Spell\Spell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use Illuminate\Support\Collection;

class SpellRepositorySwoole extends DatabaseRepository implements SpellRepositorySwooleInterface
{
    public function __construct()
    {
        parent::__construct(Spell::class);
    }

    /**
     * @inheritDoc
     */
    public function getMissingSpellIds(): array
    {
        // TODO: Implement getMissingSpellIds() method.
    }

    /**
     * @inheritDoc
     */
    public function findAllById(Collection $spellIds): Collection
    {
        // TODO: Implement findAllById() method.
    }
}
