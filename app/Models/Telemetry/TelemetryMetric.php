<?php

namespace App\Models\Telemetry;

use Database\Factories\Telemetry\TelemetryMetricFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One operational time-series data point: a scheduled command's run duration, or a site gauge
 * sampled by `scheduler:telemetry`. Replaces the retired InfluxDB sink (#4075).
 *
 * Not to be confused with the product-facing {@see \App\Models\Metrics\Metric} counters.
 *
 * @property int         $id
 * @property string      $measurement
 * @property string      $name
 * @property string|null $tag
 * @property float       $value
 * @property bool        $success     Only meaningful for the 'scheduler' measurement
 * @property Carbon      $recorded_at
 *
 * @mixin Eloquent
 */
class TelemetryMetric extends Model
{
    /** @use HasFactory<TelemetryMetricFactory> */
    use HasFactory;

    public const string MEASUREMENT_SCHEDULER           = 'scheduler';
    public const string MEASUREMENT_USER_COUNT          = 'user_count';
    public const string MEASUREMENT_TEAM_COUNT          = 'team_count';
    public const string MEASUREMENT_DUNGEON_ROUTE_COUNT = 'dungeon_route_count';
    public const string MEASUREMENT_QUEUE               = 'queue';
    public const string MEASUREMENT_MYSQL               = 'mysql';

    public $timestamps = false;

    protected $fillable = [
        'measurement',
        'name',
        'tag',
        'value',
        'success',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value'       => 'float',
            'success'     => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }
}
