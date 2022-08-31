<?php

namespace Tests\Unit\Traits;

use App\Models\Npc;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesNpc
{
    /**
     * @param array|null $attributes
     * @return Npc
     */
    public function createNpc(?array $attributes = null): Npc
    {
        return new Npc($attributes ?? $this->getNpcDefaultAttributes());
    }

    /**
     * @return int[]
     */
    public function getNpcDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }
}
