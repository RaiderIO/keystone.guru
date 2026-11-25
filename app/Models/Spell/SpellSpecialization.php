<?php

namespace App\Models\Spell;

use App\Models\CharacterClassSpecialization;
use App\Models\Traits\SeederModel;
use Database\Factories\Spell\SpellSpecializationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A specialization that can cast a spell, as the game client's DB2 tables say: a spec spell, a talent its tree lets
 * that specialization pick, or a baseline ability of its class. A spell without any row is one whose specializations
 * are unknown, not one that no specialization has.
 *
 * Travels to every environment nested under its spell in `database/seeders/dungeondata/spells.json`.
 *
 * @property int $id
 * @property int $spell_id
 * @property int $character_class_specialization_id
 *
 * @property Spell                        $spell
 * @property CharacterClassSpecialization $characterClassSpecialization
 *
 * @mixin Eloquent
 */
class SpellSpecialization extends Model
{
    /** @use HasFactory<SpellSpecializationFactory> */
    use HasFactory;
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'spell_id',
        'character_class_specialization_id',
    ];

    protected function casts(): array
    {
        return [
            'spell_id'                          => 'integer',
            'character_class_specialization_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Spell, $this>
     */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    /**
     * @return BelongsTo<CharacterClassSpecialization, $this>
     */
    public function characterClassSpecialization(): BelongsTo
    {
        return $this->belongsTo(CharacterClassSpecialization::class);
    }
}
