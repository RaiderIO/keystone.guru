<?php

namespace App\SeederHelpers\RelationImport\Parsers\Attribute;

use App\SeederHelpers\RelationImport\Parsers\ParserInterface;

/**
 * An attribute parser can adjust the contents of an attribute
 */
interface AttributeParserInterface extends ParserInterface
{
    /**
     * @param string $modelClassName
     * @param array $modelData
     * @param string $name
     * @param mixed $value
     * @return array
     */
    public function parseAttribute(string $modelClassName, array $modelData, string $name, $value): array;
}
