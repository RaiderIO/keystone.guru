<?php

namespace App\Models\CombatLog;

use App\Models\Traits\SerializesDates;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $combat_log_path
 * @property int|null    $run_id          The Raider.IO run this combat log belongs to, written by PollCombatLogRunsCommand.
 * @property bool        $extracted_data
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin Eloquent
 */
class ParsedCombatLog extends Model
{
    use SerializesDates;

    protected $connection = 'combatlog';

    public $timestamps = true;

    protected $fillable = [
        'combat_log_path',
        'run_id',
        'extracted_data',
        'created_at',
        'updated_at',
    ];

    public function casts(): array
    {
        return [
            'run_id' => 'integer',
        ];
    }
}
