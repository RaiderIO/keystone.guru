<?php

namespace App\SeederHelpers\RelationImport\Parsers\Attribute;

use App\SeederHelpers\RelationImport\Parsers\Relation\RelationParserInterface;
use Illuminate\Support\Carbon;

class TimestampAttributeParser implements AttributeParserInterface
{
    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseModel(string $modelClassName): bool
    {
        return true;
    }

    /**
     * @param string $modelClassName
     * @param array $modelData
     * @param string $name
     * @param $value
     * @return array
     */
    public function parseAttribute(string $modelClassName, array $modelData, string $name, $value): array
    {
        $fieldNames = [
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
