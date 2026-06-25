<?php

namespace Tests\Fixtures\Traits;

use App\Models\Npc\Npc;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesNpc
{
    /**
     * @param array<string, mixed>|null $attributes
     */
    public function createNpc(?array $attributes = null): Npc
    {
        return new Npc($attributes ?? $this->getNpcDefaultAttributes());
    }

    /**
     * @return array<string, int>
     */
    public function getNpcDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }
}
