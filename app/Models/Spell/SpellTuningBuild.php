<?php

namespace App\Models\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Traits\SeederModel;
use Database\Factories\Spell\SpellTuningBuildFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A client build `spell:difftuning` compared against the one before it, whether or not any
 * {@see SpellTuningChange} came out of it - so a build that changed nothing is still known to be processed.
 *
 * Travels to every environment through `database/seeders/dungeondata/spell_tuning_builds.json`.
 *
 * @property int         $id
 * @property int         $game_version_id
 * @property string      $from_build
 * @property string      $to_build
 * @property int         $to_build_number
 * @property Carbon|null $to_build_released_at When to_build went live, in UTC; null when unknown
 *
 * @property GameVersion $gameVersion
 *
 * @mixin Eloquent
 */
class SpellTuningBuild extends Model
{
    /** @use HasFactory<SpellTuningBuildFactory> */
    use HasFactory;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'game_version_id',
        'from_build',
        'to_build',
        'to_build_number',
        'to_build_released_at',
    ];

    protected function casts(): array
    {
        return [
            'game_version_id' => 'integer',
            'to_build_number' => 'integer',
            // The seeder JSON inserts this string as-is, so it must stay a format MySQL accepts
            'to_build_released_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }
}
