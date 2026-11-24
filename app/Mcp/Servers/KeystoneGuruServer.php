<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GameData\GetDungeonPacksTool;
use App\Mcp\Tools\GameData\ListDungeonsTool;
use App\Mcp\Tools\Route\AddPullTool;
use App\Mcp\Tools\Route\CreateRouteTool;
use App\Mcp\Tools\Route\GetRouteTool;
use App\Mcp\Tools\Route\ListMyRoutesTool;
use App\Mcp\Tools\Route\RemovePullTool;
use App\Mcp\Tools\Route\ReorderPullsTool;
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
    create_route, add pulls in kill order with add_pull, and check the result with validate_route. Every route you
    create is private to you. You only ever see and edit your own routes, and only those on no team.
    MARKDOWN)]
class KeystoneGuruServer extends Server
{
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListDungeonsTool::class,
        GetDungeonPacksTool::class,
        ListMyRoutesTool::class,
        GetRouteTool::class,
        CreateRouteTool::class,
        AddPullTool::class,
        ReorderPullsTool::class,
        RemovePullTool::class,
        ValidateRouteTool::class,
    ];
}
