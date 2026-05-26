<?php

namespace App\Models\CombatLog;

use App\Models\Npc\Npc;
use App\Models\Traits\HasGenericModelRelation;
use App\Models\Traits\SeederModel;
use App\Models\Traits\SerializesDates;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int                   $id
 * @property int                   $npc_id
 * @property CombatLogNpcEventType $event_type
 * @property string                $model_class
 * @property int                   $model_id
 * @property string|null           $combat_log_path
 *
 * @property Carbon $created_at
 *
 * @property Npc   $npc
 * @property Model $model
 *
 * @mixin Eloquent
 */
class CombatLogNpcEvent extends Model
{
    use HasGenericModelRelation, SeederModel, SerializesDates;

    protected $connection = 'combatlog';

    public const UPDATED_AT = null;

    protected $fillable = [
        'npc_id',
        'event_type',
        'model_class',
        'model_id',
        'combat_log_path',
    ];

    public function casts(): array
    {
        return [
            'event_type' => CombatLogNpcEventType::class,
        ];
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function model(): HasOne
    {
        return $this->hasOne($this->model_class, 'id', 'model_id');
    }
}
