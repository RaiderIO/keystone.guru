<?php


namespace App\SeederHelpers\RelationImport\Conditionals;

use App\SeederHelpers\RelationImport\Mapping\RelationMapping;

/**
 * Determines if we can import this model based on its mapping version. If this model has a newer mapping version than
 * the one that currently exists in the database, we may import it. Otherwise, we shouldn't import it.
 *
 *
 * @package App\SeederHelpers\RelationImport\Conditionals
 * @author Wouter
 * @since 27/10/2022
 */
class MappingVersionConditional implements ConditionalInterface
{
    public function shouldParseModel(RelationMapping $relationMapping, array $modelData): bool
    {
        return false;
    }
}
