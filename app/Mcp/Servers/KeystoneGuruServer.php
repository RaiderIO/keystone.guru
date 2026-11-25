<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GameData\GetDungeonPacksTool;
use App\Mcp\Tools\GameData\GetNpcTool;
use App\Mcp\Tools\GameData\ListAffixesTool;
use App\Mcp\Tools\GameData\ListClassSpellsTool;
use App\Mcp\Tools\GameData\ListDungeonNpcsTool;
use App\Mcp\Tools\GameData\ListDungeonsTool;
use App\Mcp\Tools\GameData\ListSeasonsTool;
use App\Mcp\Tools\Route\AddPullTool;
use App\Mcp\Tools\Route\CreateRouteTool;
use App\Mcp\Tools\Route\GetRouteOptionsTool;
use App\Mcp\Tools\Route\GetRouteTool;
use App\Mcp\Tools\Route\ListMyRoutesTool;
use App\Mcp\Tools\Route\RemovePullTool;
use App\Mcp\Tools\Route\ReorderPullsTool;
use App\Mcp\Tools\Route\UpdatePullTool;
use App\Mcp\Tools\Route\UpdateRouteSettingsTool;
use App\Mcp\Tools\Route\ValidateRouteTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Keystone.guru')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Keystone.guru plans World of Warcraft Mythic+ dungeon routes. A route belongs to one dungeon and is an ordered
    list of pulls; each pull kills a set of enemies, and the route must reach the dungeon's required enemy forces and
    kill every boss.

    Start with list_dungeons, then get_dungeon_packs for the dungeon you plan: it lists every pack with its enemies,
    their enemy forces, in-game coordinates (yards) and the distance to the nearest packs. Create the route with
    create_route, add pulls in kill order with add_pull, refine each pull (spells such as Bloodlust, kill area, color,
    note) with update_pull, set the party and other settings with update_route_settings, and check the result with
    validate_route. get_route_options lists the ids those tools accept. For planning, list_dungeon_npcs and get_npc
    give each NPC's enemy forces and health at a key level; list_class_spells the party's spells;
    list_seasons and list_affixes the season's affixes. Every route you create is private to you. You only ever see
    and edit your own routes, and only those on no team.
    MARKDOWN)]
class KeystoneGuruServer extends Server
{
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListDungeonsTool::class,
        GetDungeonPacksTool::class,
        ListDungeonNpcsTool::class,
        GetNpcTool::class,
        ListClassSpellsTool::class,
        ListSeasonsTool::class,
        ListAffixesTool::class,
        ListMyRoutesTool::class,
        GetRouteTool::class,
        GetRouteOptionsTool::class,
        CreateRouteTool::class,
        UpdateRouteSettingsTool::class,
        AddPullTool::class,
        UpdatePullTool::class,
        ReorderPullsTool::class,
        RemovePullTool::class,
        ValidateRouteTool::class,
    ];
}
