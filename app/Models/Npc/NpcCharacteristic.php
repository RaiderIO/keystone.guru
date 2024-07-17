<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Characteristic;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $characteristic_id
 *
 * @mixin Eloquent
 */
class NpcCharacteristic extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = ['id', 'npc_id', 'characteristic_id'];

    protected $hidden = ['npc_id'];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }
}
