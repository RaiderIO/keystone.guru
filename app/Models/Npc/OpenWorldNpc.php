<?php

namespace App\Models\Npc;

use Database\Factories\Npc\OpenWorldNpcFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An NPC that kills players in the open world, synced daily from Raider.IO's deadly-NPC list. Kept apart from
 * {@see Npc}: that table is owned by the seeder and only holds NPCs of mapped dungeon content.
 *
 * @property int         $id           The in-game NPC id
 * @property string      $name
 * @property int|null    $display_id
 * @property bool        $has_portrait
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $enemy_portrait_url
 *
 * @mixin Eloquent
 */
class OpenWorldNpc extends Model
{
    /** @use HasFactory<OpenWorldNpcFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'display_id',
        'has_portrait',
    ];

    public function getEnemyPortraitUrlAttribute(): string
    {
        return sprintf('images/enemyportraits/%d.png', $this->id);
    }

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'display_id'   => 'integer',
            'has_portrait' => 'boolean',
        ];
    }
}
