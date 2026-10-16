<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteBrushlinesRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteEnemyRaidMarkersRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteKillZoneRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteMapIconsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRoutePathsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NestedModelRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\PivotRelationParser;

class DungeonRouteRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('dungeonroutes.json', DungeonRoute::class);

        $this->setPreSaveRelationParsers(collect([
            new NestedModelRelationParser(),
        ]));

        $this->setPostSaveRelationParsers(collect([
            new PivotRelationParser(DungeonRoute::class, 'playerspecializations', DungeonRoutePlayerSpecialization::class, 'dungeon_route_id'),
            new PivotRelationParser(DungeonRoute::class, 'playerraces', DungeonRoutePlayerRace::class, 'dungeon_route_id'),
            new PivotRelationParser(DungeonRoute::class, 'playerclasses', DungeonRoutePlayerClass::class, 'dungeon_route_id'),

            new PivotRelationParser(DungeonRoute::class, 'routeattributesraw', DungeonRouteAttribute::class, 'dungeon_route_id'),

            new PivotRelationParser(DungeonRoute::class, 'affix_groups', DungeonRouteAffixGroup::class, 'dungeon_route_id'),

            new DungeonRouteBrushlinesRelationParser(),
            new DungeonRoutePathsRelationParser(),

            new DungeonRouteKillZoneRelationParser(),

            new DungeonRouteEnemyRaidMarkersRelationParser(),

            new DungeonRouteMapIconsRelationParser(),
        ]));
    }
}
