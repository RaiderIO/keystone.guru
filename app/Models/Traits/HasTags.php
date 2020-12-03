<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use App\Models\Tags\TagModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

/**
 * @property string $model_class
 *
 * @property Collection|TagModel[] $tagModels
 * @property Collection|Tag[] $tags
 *
 * @mixin Model
 */
trait HasTags
{
    /**
     * @return HasManyThrough
     */
    public function tags()
    {
        return $this->hasManyThrough('\App\Models\Tags\Tag', '\App\Models\Tags\TagModel', 'model_id', 'id')->where('model_class', get_class($this));
    }

    /**
     * @return HasMany
     */
    public function tagmodels()
    {
        return $this->hasMany('\App\Models\Tags\TagModel', 'model_id')->where('model_class', get_class($this));
    }

    public function getAvailableTags()
    {


//        return Tag::select('tag.*')
//            ->join('')
    }
}