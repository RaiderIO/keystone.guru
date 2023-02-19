<?php

namespace App\Models\Metrics;

use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property int $category
 * @property string $tag
 * @property int $value
 * @property string $updated_at
 * @property string $created_at
 *
 *
 * @package App\Models\Metrics
 * @author Wouter
 * @since 14/02/2023
 *
 * @mixin Eloquent
 */
class Metric extends Model
{
    use HasGenericModelRelation;

    public const CATEGORY_DUNGEON_ROUTE_MDT_COPY = 1;

    public const ALL_CATEGORIES = [
        self::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
    ];

    public const TAG_MDT_COPY_VIEW  = 'view';
    public const TAG_MDT_COPY_EMBED = 'embed';

    public const ALL_TAGS = [
        self::TAG_MDT_COPY_VIEW,
        self::TAG_MDT_COPY_EMBED,
    ];

    protected $fillable = [
        'model_id',
        'model_class',
        'category',
        'tag',
        'value',
    ];
}
