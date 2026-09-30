<?php

namespace App\Models\DungeonRoute;

/**
 * What produced an upgrade draft (a route with upgrade_of_dungeon_route_id set). Decides the copy of the
 * draft snackbar, the route table badge and the flashes; Apply and Discard behave the same for every source.
 */
enum DungeonRouteDraftSource: string
{
    case MappingUpgrade  = 'mapping_upgrade';
    case MdtImport       = 'mdt_import';
    case ArcRegeneration = 'arc_regeneration';
}
