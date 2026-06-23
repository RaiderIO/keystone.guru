<?php

namespace App\SeederHelpers\RelationImport\Conditionals;

use App\SeederHelpers\RelationImport\Mapping\RelationMapping;

interface ConditionalInterface
{
    /**
     * @param array<string, mixed> $modelData
     */
    public function shouldParseModel(RelationMapping $relationMapping, array $modelData): bool;
}
