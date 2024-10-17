<?php

namespace App\Models\CombatLog;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int                    $id
 * @property string                 $combat_log_path
 * @property int                    $percent_completed
 * @property CombatLogAnalyzeStatus $status
 * @property string                 $error
 * @property string                 $result
 *
 * @property Carbon                 $created_at
 * @property Carbon                 $updated_at
 *
 * @mixin Eloquent
 */
class CombatLogAnalyze extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = true;

    protected $fillable = [
        'combat_log_path',
        'percent_completed',
        'status',
        'error',
        'result',
    ];

}
