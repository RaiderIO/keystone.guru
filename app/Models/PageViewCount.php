<?php

namespace App\Models;

use Database\Factories\PageViewCountFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The number of {@see PageView}s a model received on one day from one source. Unlike the raw page views, which are
 * pruned after `keystoneguru.page_views.retention_days`, these are kept indefinitely.
 *
 * @property int    $id
 * @property string $model_class
 * @property int    $model_id
 * @property int    $source      The page view's source, 0 when it had none
 * @property Carbon $viewed_on
 * @property int    $views
 *
 * @mixin Eloquent
 */
class PageViewCount extends Model
{
    /** @use HasFactory<PageViewCountFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'model_class',
        'model_id',
        'source',
        'viewed_on',
        'views',
    ];

    protected function casts(): array
    {
        return [
            'model_id'  => 'integer',
            'source'    => 'integer',
            'viewed_on' => 'date',
            'views'     => 'integer',
        ];
    }
}
