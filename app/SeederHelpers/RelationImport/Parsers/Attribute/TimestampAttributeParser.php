<?php

namespace App\SeederHelpers\RelationImport\Parsers\Attribute;

use Illuminate\Support\Carbon;

class TimestampAttributeParser implements AttributeParserInterface
{
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
        $fieldNames = [
            'fetched_data_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($fieldNames as $fieldName) {
            if (isset($modelData[$fieldName])) {
                $modelData[$fieldName] = Carbon::createFromTimeString($modelData[$fieldName])->toDateTimeString();
            }
        }

        return $modelData;
    }
}
