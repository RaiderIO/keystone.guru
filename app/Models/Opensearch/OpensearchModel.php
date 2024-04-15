<?php

namespace App\Models\Opensearch;

use Codeart\OpensearchLaravel\OpenSearchable;
use Ramsey\Uuid\Uuid;

abstract class OpensearchModel implements OpenSearchable
{
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function generateId(): string
    {
        return Uuid::uuid7();
    }
}
