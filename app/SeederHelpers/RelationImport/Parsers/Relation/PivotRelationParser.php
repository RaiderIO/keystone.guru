<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;

/**
 * Imports the rows of a plain child/pivot table that hangs off a parent model.
 *
 * Two shapes exist, selected by $parentKeyColumn:
 *  - Set: the JSON rows do not carry the parent's key (parents with a database-generated id, imported post-save).
 *    The parent's id is written into that column on every row, and each row is inserted individually.
 *  - Null: the JSON rows already carry their own keys (parents with a stable id, imported pre-save).
 *    The rows are inserted as one batch untouched.
 *
 * Rows go through DatabaseSeeder::getTempTableName(), which resolves to the staging table for SeederModel pivots
 * and to the regular table for every other model.
 */
class PivotRelationParser implements RelationParserInterface
{
    /**
     * @param class-string<Model> $parentModelClass The model whose JSON entry holds the relation
     * @param string              $relationName     The JSON key of the relation on the parent
     * @param class-string<Model> $pivotModelClass  The model the rows are inserted as
     * @param string|null         $parentKeyColumn  Column on the pivot to fill with the parent's id, null if the rows carry their own
     */
    public function __construct(
        private readonly string  $parentModelClass,
        private readonly string  $relationName,
        private readonly string  $pivotModelClass,
        private readonly ?string $parentKeyColumn = null,
    ) {
    }

    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === $this->parentModelClass;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === $this->relationName;
    }

    /**
     * @param  array<string, mixed>     $modelData
     * @param  array<int|string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        $query = $this->pivotModelClass::from(DatabaseSeeder::getTempTableName($this->pivotModelClass));

        if ($this->parentKeyColumn === null) {
            $query->insert($value);
        } else {
            foreach ($value as $row) {
                $row[$this->parentKeyColumn] = $modelData['id'];

                $query->insert($row);
            }
        }

        // Only a side table was handled; the parent row is unchanged.
        return $modelData;
    }
}
