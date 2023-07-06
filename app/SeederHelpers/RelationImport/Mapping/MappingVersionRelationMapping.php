<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Mapping\MappingVersion;
use App\SeederHelpers\RelationImport\Parsers\Attribute\TimestampAttributeParser;

class MappingVersionRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('mapping_versions.json', MappingVersion::class);

        $this->setAttributeParsers(collect([
            new TimestampAttributeParser(),
        ]));
    }
}
