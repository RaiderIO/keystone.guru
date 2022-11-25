<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Mapping\MappingCommitLog;
use App\SeederHelpers\RelationImport\Parsers\Attribute\TimestampAttributeParser;

class MappingCommitLogRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('mapping_commit_logs.json', MappingCommitLog::class);

        $this->setAttributeParsers(collect([
            new TimestampAttributeParser(),
        ]));
    }
}
