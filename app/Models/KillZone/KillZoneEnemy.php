<?php

namespace App\Models\KillZone;

use App\Models\Enemy;
use App\Models\Npc\Npc;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $kill_zone_id
 * @property int $npc_id
 * @property int $mdt_id
 * @property int $enemy_id
 *
 * @property KillZone $killZone
 * @property Npc      $npc
 * @property Enemy    $enemy
 *
 * @mixin Eloquent
 */
class KillZoneEnemy extends Model
{
    use HasFactory;

    public $hidden = [
        'id',
        'kill_zone_id',
    ];

    public $timestamps = false;

    protected $fillable = [
        'kill_zone_id',
        'npc_id',
        'mdt_id',
        'enemy_id',
    ];

    protected function casts(): array
    {
        return [
            'kill_zone_id' => 'integer',
            'npc_id'       => 'integer',
            'mdt_id'       => 'integer',
            'enemy_id'     => 'integer',
        ];
    }

    public function killZone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
