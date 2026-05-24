<?php

namespace App\Models\CombatLog;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Traits\HasGenericModelRelation;
use Database\Factories\CombatLog\CombatLogParsingCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int          $id
 * @property int          $combat_log_version
 * @property class-string $model_class
 * @property int          $model_id
 * @property Carbon       $date
 * @property int          $count
 * @property int          $threshold
 */
class CombatLogParsingCriterion extends Model
{
    /** @use HasFactory<CombatLogParsingCriterionFactory> */
    use HasFactory;
    use HasGenericModelRelation;

    /**
     * Maps each valid criterion model class to the relations it requires eager-loaded
     * so getName() can be called on every result without N+1 queries.
     *
     * @var array<class-string<CombatLogCriterionModelInterface>, list<string>>
     */
    public const array VALID_CRITERIA = [
        Dungeon::class                      => [],
        CharacterClassSpecialization::class => ['class'],
    ];

    public $timestamps = false;

    protected $fillable = [
        'combat_log_version',
        'model_class',
        'model_id',
        'date',
        'count',
        'threshold',
    ];

    protected static function newFactory(): CombatLogParsingCriterionFactory
    {
        return CombatLogParsingCriterionFactory::new();
    }

    public function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
