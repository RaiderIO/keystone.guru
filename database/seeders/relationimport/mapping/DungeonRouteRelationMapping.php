<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\DungeonRoute;
use Database\Seeders\RelationImport\Parsers\DungeonRouteAffixGroupRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRouteAttributesRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRouteBrushlinesRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRouteEnemyRaidMarkersRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRouteKillZoneRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRouteMapIconsRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRoutePathsRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRoutePlayerClassRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRoutePlayerRaceRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRoutePlayerSpecializationRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonRoutePridefulEnemiesRelationParser;
use Database\Seeders\RelationImport\Parsers\NestedModelRelationParser;

class DungeonRouteRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeonroutes.json', DungeonRoute::class);

        $this->setPreSaveAttributeParsers(collect([
            new NestedModelRelationParser(),
        ]));

        $this->setPostSaveAttributeParsers(collect([
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

            new DungeonRouteMapIconsRelationParser()
        ]));
    }

}