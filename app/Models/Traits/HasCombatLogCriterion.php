<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 *
 * @mixin Model
 */
trait HasCombatLogCriterion
{
    public function getName(): string
    {
        return __($this->name);
    }

    public function getImageLink(): ?string
    {
        return null;
    }
}
