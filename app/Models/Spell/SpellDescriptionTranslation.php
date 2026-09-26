<?php

namespace App\Models\Spell;

use App\Models\Traits\SeederModel;
use App\Service\WagoTools\GameLocale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A spell's description in one non-English locale, rendered from the game client's own text for that
 * locale rather than translated by us.
 *
 * It travels as a format plus its values for the same reason the English one does: the numbers in a
 * description are coefficients that a key level rescales, and they are not in the same order in every
 * language.
 *
 * @property int                    $id
 * @property int                    $spell_id
 * @property string                 $locale             the game client's locale code, e.g. `deDE`
 * @property string                 $description_format
 * @property array<int, mixed>|null $description_values
 *
 * @property Spell $spell
 */
class SpellDescriptionTranslation extends Model
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'spell_id',
        'locale',
        'description_format',
        'description_values',
    ];

    protected function casts(): array
    {
        return [
            'spell_id'           => 'integer',
            'description_values' => 'array',
        ];
    }

    public function gameLocale(): GameLocale
    {
        return GameLocale::from($this->locale);
    }

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
