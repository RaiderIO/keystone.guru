<?php

namespace App\SeederHelpers\RelationImport\Parsers\Attribute;

/**
 * A json column arrives from the seeder file already decoded, since the seeder file is itself json.
 * The temp-table insert binds values straight onto the query, which cannot take an array - so anything
 * cast to `array` on the model is encoded again on the way in.
 */
class JsonAttributeParser implements AttributeParserInterface
{
    /** @param array<int, string> $fieldNames */
    public function __construct(private readonly array $fieldNames)
    {
    }

    public function canParseModel(string $modelClassName): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed> $modelData
     * @return array<string, mixed>
     */
    public function parseAttribute(string $modelClassName, array $modelData, string $name, mixed $value): array
    {
        foreach ($this->fieldNames as $fieldName) {
            if (is_array($modelData[$fieldName] ?? null)) {
                $modelData[$fieldName] = json_encode($modelData[$fieldName]);
            }
        }

        return $modelData;
    }
}
