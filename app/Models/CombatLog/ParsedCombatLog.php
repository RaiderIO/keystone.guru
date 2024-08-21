<?php

namespace App\Models\CombatLog;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $combat_log_path
 * @property bool   $extracted_data
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class ParsedCombatLog extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = true;

    protected $fillable = [
        'combat_log_path',
        'run_id',
        'extracted_data',
        'created_at',
        'updated_at',
    ];
}
