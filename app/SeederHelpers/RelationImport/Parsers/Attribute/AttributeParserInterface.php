<?php

namespace App\SeederHelpers\RelationImport\Parsers\Attribute;

use App\SeederHelpers\RelationImport\Parsers\ParserInterface;

/**
 * An attribute parser can adjust the contents of an attribute
 */
interface AttributeParserInterface extends ParserInterface
{
    public function parseAttribute(string $modelClassName, array $modelData, string $name, mixed $value): array;
}
