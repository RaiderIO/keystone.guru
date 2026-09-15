<?php

namespace App\Models\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Traits\SeederModel;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;
use Database\Factories\Spell\SpellTuningChangeFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One change in a spell's description numbers between two client builds, as found by
 * `spell:difftuning` (see {@see \App\Service\Spell\Tuning\SpellTuningDiffService}).
 *
 * Rows are computed once per build pair on a dev machine and travel to every environment through
 * `database/seeders/dungeondata/spell_tuning_changes.json`, like the spells they describe.
 *
 * @property int                            $id
 * @property int                            $game_version_id
 * @property int                            $spell_id
 * @property string                         $from_build
 * @property string                         $to_build
 * @property int                            $to_build_number
 * @property Carbon|null                    $to_build_released_at When to_build went live, in UTC; null when unknown
 * @property SpellTuningChangeType          $change_type
 * @property int|null                       $value_index          Position in `spells.description_values`; null for a rewritten description
 * @property SpellDescriptionValueKind|null $kind
 * @property float|null                     $old_coefficient
 * @property float|null                     $new_coefficient
 * @property string|null                    $old_text
 * @property string|null                    $new_text
 * @property float|null                     $delta                new / old - 1 for scalable values, null otherwise
 *
 * @property Spell       $spell
 * @property GameVersion $gameVersion
 *
 * @mixin Eloquent
 */
class SpellTuningChange extends Model
{
    /** @use HasFactory<SpellTuningChangeFactory> */
    use HasFactory;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'game_version_id',
        'spell_id',
        'from_build',
        'to_build',
        'to_build_number',
        'to_build_released_at',
        'change_type',
        'value_index',
        'kind',
        'old_coefficient',
        'new_coefficient',
        'old_text',
        'new_text',
        'delta',
    ];

    protected function casts(): array
    {
        return [
            'game_version_id' => 'integer',
            'spell_id'        => 'integer',
            'to_build_number' => 'integer',
            // The seeder JSON inserts this string as-is, so it must stay a format MySQL accepts
            'to_build_released_at' => 'datetime:Y-m-d H:i:s',
            'change_type'          => SpellTuningChangeType::class,
            'value_index'          => 'integer',
            'kind'                 => SpellDescriptionValueKind::class,
            'old_coefficient'      => 'float',
            'new_coefficient'      => 'float',
            'delta'                => 'float',
        ];
    }

    /** Whether this is a damage/healing change, i.e. one expressed as a coefficient and a delta. */
    public function isScalable(): bool
    {
        return $this->kind?->isScalable() ?? false;
    }

    /** The delta as a percentage (+33.3 for a third more), or null when there is none. */
    public function getDeltaPercent(): ?float
    {
        return $this->delta === null ? null : $this->delta * 100;
    }

    /**
     * Whether both builds rendered an actual number for this value. A scalable value renders nothing
     * when the spell has no damage multiplier, in which case only the coefficients can be shown.
     */
    public function hasRenderedTexts(): bool
    {
        return ($this->old_text ?? '') !== '' && ($this->new_text ?? '') !== '';
    }

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }
}
