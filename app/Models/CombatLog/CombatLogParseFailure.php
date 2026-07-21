<?php

namespace App\Models\CombatLog;

use App\Models\Season;
use App\Models\Traits\SerializesDates;
use Database\Factories\CombatLog\CombatLogParseFailureFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $run_id
 * @property int|null    $season_id
 * @property int|null    $combat_log_version
 * @property int|null    $line_number
 * @property string|null $raw_line
 * @property string      $message
 * @property string      $exception_class
 * @property Carbon|null $resolved_at
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class CombatLogParseFailure extends Model
{
    /** @use HasFactory<CombatLogParseFailureFactory> */
    use HasFactory, SerializesDates;

    protected $connection = 'combatlog';

    protected $fillable = [
        'run_id',
        'season_id',
        'combat_log_version',
        'line_number',
        'raw_line',
        'message',
        'exception_class',
        'resolved_at',
    ];

    public function casts(): array
    {
        return [
            'run_id'             => 'integer',
            'season_id'          => 'integer',
            'combat_log_version' => 'integer',
            'line_number'        => 'integer',
            'resolved_at'        => 'datetime',
        ];
    }

    /**
     * The related {@see Season} lives on the main database connection, so it cannot be eager-loaded across
     * connections. Resolve it on demand instead - used when re-fetching the Raider.IO download URLs.
     */
    public function season(): ?Season
    {
        return $this->season_id !== null ? Season::find($this->season_id) : null;
    }
}
