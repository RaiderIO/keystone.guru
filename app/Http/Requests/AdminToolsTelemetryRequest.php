<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AdminToolsTelemetryRequest extends FormRequest
{
    public const string RANGE_24_HOURS = '24h';
    public const string RANGE_7_DAYS   = '7d';
    public const string RANGE_30_DAYS  = '30d';
    public const string RANGE_90_DAYS  = '90d';
    public const string RANGE_1_YEAR   = '1y';

    private const string DEFAULT_RANGE = self::RANGE_24_HOURS;

    /**
     * How far back each range reaches, and how coarsely its data points are bucketed.
     *
     * A bucket is one point on the chart: at five-minute sampling, 90 days of per-hour buckets is already
     * 2160 points per line, so the longer ranges roll up to a day to keep the response and the chart readable.
     *
     * @var array<string, array{hours: int, bucketSizeMinutes: int}>
     */
    private const array RANGES = [
        self::RANGE_24_HOURS => ['hours' => 24, 'bucketSizeMinutes' => 1],
        self::RANGE_7_DAYS   => ['hours' => 24 * 7, 'bucketSizeMinutes' => 60],
        self::RANGE_30_DAYS  => ['hours' => 24 * 30, 'bucketSizeMinutes' => 60],
        self::RANGE_90_DAYS  => ['hours' => 24 * 90, 'bucketSizeMinutes' => 1440],
        self::RANGE_1_YEAR   => ['hours' => 24 * 365, 'bucketSizeMinutes' => 1440],
    ];

    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', sprintf('in:%s', implode(',', self::getRanges()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'range.in' => __('validation.custom.telemetry_range.in'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getRanges(): array
    {
        return array_keys(self::RANGES);
    }

    public function getRange(): string
    {
        $range = $this->validated('range');

        return $range === null ? self::DEFAULT_RANGE : (string)$range;
    }

    /**
     * The oldest moment the selected range covers.
     */
    public function getFrom(): Carbon
    {
        return Carbon::now()->subHours(self::RANGES[$this->getRange()]['hours']);
    }

    public function getBucketSizeMinutes(): int
    {
        return self::RANGES[$this->getRange()]['bucketSizeMinutes'];
    }
}
