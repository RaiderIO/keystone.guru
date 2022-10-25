<?php

namespace Database\Seeders\RelationImport\Parsers;

/**
 * A RelationParser can convert a saved relation in the JSON file back to the database. This way we can save the model
 * with normalized data. This keeps the data a bit more compact and keeps relevant info next to each other for versioning.
 *
 * This Interface (well, the implementing classes)'s job is to convert that normalized data back into the table where
 * the data belongs. Example; a model saves an npc_id which references to the npc it needs. In Laravel, this relation
 * can be described with an 'npc' object. The npc object cannot be saved into its database directly. The ID from that object
 * needs to be extracted and put back in the original object (since that's missing, npc_id can be found by going npc->id).
 * This is what the NestedModelRelationParser is for.
 *
 * Another use case is the Vertices that are saved along with a few things (EnemyPacks, Patrols). These need to be extracted
 * and saved to the database again.
 */
interface RelationParser
{
    public function canParseModel(string $modelClassName): bool;

    public function canParseRelation(string $name, array $value): bool;

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array;
}
