<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\GameVersion\GameVersion;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $npc_id
 * @property int $game_version_id
 * @property int $health
 * @property int $percentage
 *
 * @property Npc         $npc
 * @property GameVersion $gameVersion
 *
 * @mixin Eloquent
 */
class NpcHealth extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'npc_id',
        'game_version_id',
        'health',
        'percentage',
    ];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }
}
