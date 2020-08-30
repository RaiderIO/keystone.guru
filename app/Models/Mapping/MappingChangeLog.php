<?php

namespace App\Models\Mapping;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $before_model
 * @property string|null $after_model
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingChangeLog extends Model
{
    protected $fillable = ['model_id', 'model_class', 'before_model', 'after_model'];

    /**
     * @return HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }

    public function shouldSynchronize(MappingCommitLog $mostRecentMappingCommitLog): bool
    {
        // If there is a more recent mapping change that we should update
        return $this->created_at->isAfter($mostRecentMappingCommitLog->created_at) &&
            // If the most recent change was far away enough in time
            $this->created_at->addHours(config('keystoneguru.mapping_commit_after_change_hours'))->isPast();
    }
}
