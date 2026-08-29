<?php

namespace App\Models\CombatLog;

use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
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
 * @property int          $mythic_level_min
 * @property int|null     $mythic_level_max
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
     * When adding a new criterion model, these are the files that need updating alongside it:
     * - app/Console/Commands/CombatLog/PollCombatLogRunsCommand.php - which Raider.IO query the
     *   criterion polls with, and which criteria a dispatched run is recorded against
     * - app/Service/CombatLog/CombatLogParsingCriteriaService.php - getAllModelsForCriteria()
     * - app/Service/RaiderIO/Dtos/SearchAdvancedRunsFilter.php and RaiderIOApiService, when the
     *   criterion needs a filter dimension the search API doesn't take yet
     *
     * @var array<class-string<CombatLogCriterionModelInterface>, list<string>>
     */
    public const array VALID_CRITERIA = [
        Dungeon::class                      => [],
        CharacterClassSpecialization::class => ['class'],
        CharacterRace::class                => [],
    ];

    public $timestamps = false;

    protected $fillable = [
        'combat_log_version',
        'model_class',
        'model_id',
        'mythic_level_min',
        'mythic_level_max',
        'date',
        'count',
        'threshold',
    ];

    /**
     * Whether this row tracks the open ended band of the highest keys of the season. Those runs
     * are always parsed, so the row is a counter for visibility only - it has no budget and its
     * threshold is meaningless.
     */
    public function isTopBand(): bool
    {
        return $this->mythic_level_max === null;
    }

    public function getBandName(): string
    {
        return $this->isTopBand()
            ? sprintf('%d+', $this->mythic_level_min)
            : sprintf('%d-%d', $this->mythic_level_min, $this->mythic_level_max);
    }

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
