<?php

namespace App\Models\Metrics;

use App\Models\CacheModel;
use App\Models\Traits\HasGenericModelRelation;
use Eloquent;

/**
 * @property int $model_id
 * @property string $model_class
 * @property int $category
 * @property string $tag
 * @property int $value
 * @property string $updated_at
 * @property string $created_at
 *
 * @package App\Models\Metrics
 * @author Wouter
 * @since 16/02/2023
 *
 * @mixin Eloquent
 */
class MetricAggregation extends CacheModel
{
    use HasGenericModelRelation;

    protected $visible = [
        'category',
        'tag',
        'value',
    ];

    protected $fillable = [
        'model_id',
        'model_class',
        'category',
        'tag',
        'value',
    ];
}
