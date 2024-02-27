<?php

namespace App\Models\Traits;

use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait SeederModel
{
    public static function boot()
    {
        parent::boot();

        // While we're seeding, fix the database table
        //        $fixDbTableFn = function (Model $model) {
        //            if (DatabaseSeeder::$running && !Str::endsWith($model->getTable(), DatabaseSeeder::TEMP_TABLE_SUFFIX)) {
        //                dump($model->id, DatabaseSeeder::getTempTableName(get_class($model)));
        //                $model->setTable(DatabaseSeeder::getTempTableName(get_class($model)));
        //            }
        //        };
        //        static::retrieved($fixDbTableFn);
        //        static::creating($fixDbTableFn);
        //        static::updating($fixDbTableFn);
        //        static::saving($fixDbTableFn);

        // This model may NOT be deleted, it's read only!
        static::deleting(static fn (Model $model) => $model instanceof MappingVersion || $model instanceof Floor || $model instanceof Enemy);
    }
}
