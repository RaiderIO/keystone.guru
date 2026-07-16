<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single MDT addon release: the `addonVersion` integer MDT stamps into export strings (its own
 * `tonumber(version:gsub("%.", ""))` encoding, e.g. 6115 for v6.1.15) mapped to the date its upstream
 * GitHub release was published. The integer is NOT orderable across MDT's historical schemes, so it is
 * only ever a lookup key — all ordering/comparison happens on `released_at`.
 *
 * @property int    $addon_version
 * @property Carbon $released_at
 *
 * @mixin Eloquent
 */
class MDTAddonVersion extends Model
{
    /** @var string Prevent MDT being translated to m_d_t */
    protected $table = 'mdt_addon_versions';

    protected $primaryKey = 'addon_version';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'addon_version',
        'released_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }
}
