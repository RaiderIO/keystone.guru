<?php

namespace Tests\Unit\Fixtures\Traits;

use App\Models\Enemy;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesEnemy
{
    public function createEnemy(?array $attributes = null): Enemy
    {
        return new Enemy($attributes ?? $this->getEnemyDefaultAttributes());
    }

    /**
     * @return int[]
     */
    public function getEnemyDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }
}
