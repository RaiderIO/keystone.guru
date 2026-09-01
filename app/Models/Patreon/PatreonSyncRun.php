<?php

namespace App\Models\Patreon;

use Database\Factories\Patreon\PatreonSyncRunFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One run of `patreon:refreshmembers` and how much of the campaign it managed to fetch.
 *
 * @property int         $id
 * @property Carbon      $started_at
 * @property Carbon|null $finished_at
 * @property int         $pages_fetched
 * @property int         $members_fetched
 * @property bool        $truncated
 * @property int         $members_applied
 * @property int         $members_not_linked
 * @property int         $members_unknown_benefits
 * @property int         $members_unknown_tiers
 * @property int         $members_failed
 * @property bool        $successful
 * @property string|null $failure_reason
 *
 * @mixin Eloquent
 */
class PatreonSyncRun extends Model
{
    /** @use HasFactory<PatreonSyncRunFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'started_at',
        'finished_at',
        'pages_fetched',
        'members_fetched',
        'truncated',
        'members_applied',
        'members_not_linked',
        'members_unknown_benefits',
        'members_unknown_tiers',
        'members_failed',
        'successful',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at'               => 'datetime',
            'finished_at'              => 'datetime',
            'pages_fetched'            => 'integer',
            'members_fetched'          => 'integer',
            'truncated'                => 'boolean',
            'members_applied'          => 'integer',
            'members_not_linked'       => 'integer',
            'members_unknown_benefits' => 'integer',
            'members_unknown_tiers'    => 'integer',
            'members_failed'           => 'integer',
            'successful'               => 'boolean',
        ];
    }
}
