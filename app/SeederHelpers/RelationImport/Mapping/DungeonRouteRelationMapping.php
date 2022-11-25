<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\DungeonRoute;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteAffixGroupRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteAttributesRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteBrushlinesRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteEnemyRaidMarkersRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteKillZoneRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteMapIconsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePathsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePlayerClassRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePlayerRaceRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePlayerSpecializationRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePridefulEnemiesRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NestedModelRelationParser;

class DungeonRouteRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeonroutes.json', DungeonRoute::class);

        $this->setPreSaveRelationParsers(collect([
            new NestedModelRelationParser(),
        ]));

        $this->setPostSaveRelationParsers(collect([
            new DungeonRoutePlayerSpecializationRelationParser(),
            new DungeonRoutePlayerRaceRelationParser(),
            new DungeonRoutePlayerClassRelationParser(),

            new DungeonRouteAttributesRelationParser(),

            new DungeonRouteAffixGroupRelationParser(),

            new DungeonRouteBrushlinesRelationParser(),
            new DungeonRoutePathsRelationParser(),

            new DungeonRouteKillZoneRelationParser(),

            new DungeonRouteEnemyRaidMarkersRelationParser(),
            new DungeonRoutePridefulEnemiesRelationParser(),

            new DungeonRouteMapIconsRelationParser(),
        ]));
    }

}
