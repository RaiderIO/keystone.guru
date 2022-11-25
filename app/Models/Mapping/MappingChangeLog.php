<?php

namespace App\Models\Mapping;

use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
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
    use HasGenericModelRelation;

    protected $fillable = ['dungeon_id', 'model_id', 'model_class', 'before_model', 'after_model'];

    /**
     * @param MappingCommitLog $mostRecentMappingCommitLog
     * @return bool
     */
    public function shouldSynchronize(MappingCommitLog $mostRecentMappingCommitLog): bool
    {
        // If there is a more recent mapping change that we should update
        return $this->created_at->isAfter($mostRecentMappingCommitLog->created_at) &&
            // If the most recent change was far away enough in time
            $this->created_at->addHours(config('keystoneguru.mapping_commit_after_change_hours'))->isPast();
    }
}
