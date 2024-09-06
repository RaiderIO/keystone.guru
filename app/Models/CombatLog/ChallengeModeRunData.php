<?php

namespace App\Models\CombatLog;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int              $id
 * @property int              $challenge_mode_run_id
 * @property string           $run_id
 * @property string           $correlation_id
 * @property string           $post_body
 * @property bool             $processed
 *
 * @property ChallengeModeRun $challengeModeRun
 *
 * @author Wouter
 *
 * @since 06/07/2023
 *
 * @mixin Eloquent
 */
class ChallengeModeRunData extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = false;

    protected $fillable = [
        'challenge_mode_run_id',
        'run_id',
        'correlation_id',
        'post_body',
        'processed',
    ];

    public function challengeModeRun(): BelongsTo
    {
        return $this->belongsTo(ChallengeModeRun::class);
    }
}
