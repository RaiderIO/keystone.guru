<?php

namespace App\Models\Opensearch;

use Codeart\OpensearchLaravel\OpenSearchable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 */
abstract class OpensearchModel extends Model implements OpenSearchable
{
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }
}
