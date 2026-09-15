<?php

namespace App\Models\Spell;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The game build `wagotools:importspelldescriptions` last actually imported, per game version. Written
 * by {@see \App\Service\Spell\Description\SpellDescriptionImportService} after a successful import, and
 * read by the scheduled patch check that files a GitHub issue when wago.tools has moved past it.
 *
 * @property int    $game_version_id
 * @property string $product
 * @property string $build
 * @property Carbon $imported_at
 *
 * @mixin Eloquent
 */
class SpellDescriptionImportState extends Model
{
    protected $table = 'spell_description_import_states';

    protected $primaryKey = 'game_version_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'game_version_id',
        'product',
        'build',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }
}
