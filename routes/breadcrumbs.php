<?php

/** $trail->parent() has the wrong method signature */

/** @noinspection PhpParamsInspection */

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor;
use App\Models\Npc;
use App\Models\Release;
use App\Models\Spell;
use App\Models\Team;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator;

/**
 * Home page
 */
Breadcrumbs::for('home', function (Generator $trail)
{
    $trail->push(__('breadcrumbs.home.keystone_guru'), route('home'));
});

/**
 * Site pages
 */
Breadcrumbs::for('misc.affixes', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.affixes'), route('misc.affixes'));
});
Breadcrumbs::for('misc.changelog', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.changelog'), route('misc.changelog'));
});


/**
 * Routes page
 */
Breadcrumbs::for('dungeonroutes', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.routes'), route('dungeonroutes'));
});
Breadcrumbs::for('dungeonroute.discover.search', function (Generator $trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.search'), route('dungeonroutes.search'));
});

/**
 * General categories
 */
Breadcrumbs::for('dungeonroutes.popular', function (Generator $trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.popular'), route('dungeonroutes.popular'));
});

Breadcrumbs::for('dungeonroutes.nextweek', function (Generator $trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.next_week_affixes'), route('dungeonroutes.nextweek'));
});

Breadcrumbs::for('dungeonroutes.thisweek', function (Generator $trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.this_week_affixes'), route('dungeonroutes.thisweek'));
});

Breadcrumbs::for('dungeonroutes.new', function (Generator $trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.new'), route('dungeonroutes.new'));
});


/**
 * General for a dungeon
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon', function (Generator $trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes');
    $trail->push(__($dungeon->name), route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon]));
});

/**
 * Dungeon categories
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon.popular', function (Generator $trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.popular'), route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.nextweek', function (Generator $trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.next_week_affixes'), route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.thisweek', function (Generator $trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.this_week_affixes'), route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.new', function (Generator $trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.new'), route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon]));
});

/**
 * User profile pages
 */
Breadcrumbs::for('profile.edit', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.my_profile'), route('profile.edit'));
});

Breadcrumbs::for('profile.routes', function (Generator $trail)
{
    $trail->parent('profile.edit');
    $trail->push(__('breadcrumbs.home.my_routes'), route('profile.routes'));
});

Breadcrumbs::for('profile.tags', function (Generator $trail)
{
    $trail->parent('profile.edit');
    $trail->push(__('breadcrumbs.home.my_tags'), route('profile.tags'));
});

/**
 * Teams pages
 */
Breadcrumbs::for('team.list', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.my_teams'), route('team.list'));
});

Breadcrumbs::for('team.edit', function (Generator $trail, Team $team)
{
    $trail->parent('team.list');
    if ($team === null) {
        $trail->push(__('breadcrumbs.home.new_team'), route('team.new'));
    } else {
        $trail->push(__('breadcrumbs.home.edit_team'), route('team.edit', $team));
    }
});

Breadcrumbs::for('team.invite', function (Generator $trail, Team $team)
{
    $trail->parent('team.list');
    $trail->push(__('breadcrumbs.home.join_team'), route('team.invite', $team));
});


/**
 * Admin pages
 */
Breadcrumbs::for('admin', function (Generator $trail)
{
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.admin.admin'));
});

// Tools
Breadcrumbs::for('admin.tools.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('Admin tools'), route('admin.tools'));
});
Breadcrumbs::for('admin.tools.datadump.viewexporteddungeondata', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_exported_dungeondata'), route('admin.tools.datadump.exportdungeondata'));
});
Breadcrumbs::for('admin.tools.datadump.viewexportedrelease', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_exported_releases'), route('admin.tools.datadump.exportreleases'));
});
Breadcrumbs::for('admin.tools.exception.select', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.select_exception'), route('admin.tools.exception.select'));
});
Breadcrumbs::for('admin.tools.mdt.diff', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.mdt_diff'), route('admin.tools.mdt.diff'));
});
Breadcrumbs::for('admin.tools.mdt.string', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_mdt_string_contents'), route('admin.tools.mdt.dungeonroute.viewasstring'));
});
Breadcrumbs::for('admin.tools.npcimport.import', function (Generator $trail)
{
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.import_npcs'), route('admin.tools.npcimport'));
});

// Releases
Breadcrumbs::for('admin.release.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.releases'), route('admin.releases'));
});
Breadcrumbs::for('admin.release.edit', function (Generator $trail, ?Release $release)
{
    $trail->parent('admin.release.list');
    if ($release === null) {
        $trail->push(__('breadcrumbs.home.admin.new_release'), route('admin.release.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.edit_release'), route('admin.release.edit', $release));
    }
});

// Expansions
Breadcrumbs::for('admin.expansion.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.expansions.expansions'), route('admin.expansions'));
});
Breadcrumbs::for('admin.expansion.edit', function (Generator $trail, ?Expansion $expansion)
{
    $trail->parent('admin.expansion.list');
    if ($expansion === null) {
        $trail->push(__('breadcrumbs.home.admin.expansions.new_expansion'), route('admin.expansion.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.expansions.edit_expansion'), route('admin.expansion.edit', $expansion));
    }
});

// Dungeons
Breadcrumbs::for('admin.dungeon.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.dungeons.dungeons'), route('admin.dungeons'));
});
Breadcrumbs::for('admin.dungeon.edit', function (Generator $trail, ?Dungeon $dungeon)
{
    $trail->parent('admin.dungeon.list');
    if ($dungeon === null) {
        $trail->push(__('breadcrumbs.home.admin.dungeons.new_dungeon'), route('admin.dungeon.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.dungeons.edit_dungeon'), route('admin.dungeon.edit', $dungeon));
    }
});
Breadcrumbs::for('admin.floor.edit', function (Generator $trail, Dungeon $dungeon, ?Floor $floor)
{
    $trail->parent('admin.dungeon.edit', $dungeon);
    if ($floor === null) {
        $trail->push(__('breadcrumbs.home.admin.floors.new_floor'), route('admin.floor.new', ['dungeon' => $dungeon]));
    } else {
        $trail->push(__('breadcrumbs.home.admin.floors.edit_floor'), route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $floor]));
    }
});


// Npcs
Breadcrumbs::for('admin.npc.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.npcs.npcs'), route('admin.npcs'));
});
Breadcrumbs::for('admin.npc.edit', function (Generator $trail, ?Npc $npc)
{
    $trail->parent('admin.npc.list');
    if ($npc === null) {
        $trail->push(__('breadcrumbs.home.admin.npcs.new_npc'), route('admin.npc.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.npcs.edit_npc'), route('admin.npc.edit', $npc));
    }
});

// Spells
Breadcrumbs::for('admin.spell.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.spells.spells'), route('admin.spells'));
});
Breadcrumbs::for('admin.spell.edit', function (Generator $trail, ?Spell $spell)
{
    $trail->parent('admin.spell.list');
    if ($spell === null) {
        $trail->push(__('breadcrumbs.home.admin.spells.new_spell'), route('admin.spell.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.spells.edit_spell'), route('admin.spell.edit', $spell));
    }
});

// Users
Breadcrumbs::for('admin.user.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.users.users'), route('admin.users'));
});

// User reports
Breadcrumbs::for('admin.userreport.list', function (Generator $trail)
{
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.user_reports.user_reports'), route('admin.userreports'));
});