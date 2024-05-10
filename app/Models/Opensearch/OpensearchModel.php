<?php

namespace App\Models\Opensearch;

use Codeart\OpensearchLaravel\OpenSearchable;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    public static function openSearchResultToModels(array $rows): Collection
    {
        $result = collect();

        foreach ($rows['hits']['hits'] as $hit) {
            $result->push(
                (new static())->openSearchArrayToModel($hit['_source'])
            );
        }

        return $result;
    }

    public abstract function openSearchArrayToModel(array $row): self;
}
