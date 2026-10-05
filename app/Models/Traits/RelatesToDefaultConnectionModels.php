<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * For models on a non-default connection: related models that declare no connection of their own are queried on
 * the default connection instead of inheriting this model's connection.
 */
trait RelatesToDefaultConnectionModels
{
    /**
     * @template TRelatedModel of Model
     *
     * @param  class-string<TRelatedModel> $class
     * @return TRelatedModel
     */
    protected function newRelatedInstance($class)
    {
        return new $class();
    }
}
