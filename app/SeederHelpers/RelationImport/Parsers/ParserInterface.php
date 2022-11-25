<?php

namespace App\SeederHelpers\RelationImport\Parsers;

/**
 * A parser interface checks if a parser can parse a certain model or not.
 */
interface ParserInterface
{
    public function canParseModel(string $modelClassName): bool;
}
