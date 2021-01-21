<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $model_class
 *
 * @property Collection|Tag[] $tags
 *
 * @mixin Model
 */
trait HasTags
{
    /**
     * @return hasMany
     */
    public function tags()
    {
        return $this->hasMany('\App\Models\Tags\Tag', 'model_id')->where('model_class', get_class($this));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasTag(string $name): bool
    {
        return in_array($name, $this->tags->pluck(['name'])->toArray());
    }
}