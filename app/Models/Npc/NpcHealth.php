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

    /**
     * The health an NPC carries when we never learned its real value - a placeholder rather than a
     * small enemy, which is why it is not worth putting in front of anyone (#4094).
     */
    public const int HEALTH_PLACEHOLDER = 10000;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'npc_id',
        'game_version_id',
        'health',
        'percentage',
    ];

    /** @return BelongsTo<Npc, $this> */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }
}
