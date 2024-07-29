<?php

/** $trail->parent() has the wrong method signature */

/** @noinspection PhpParamsInspection */

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Release;
use App\Models\Season;
use App\Models\Spell;
use App\Models\Team;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator;

/**
 * Home page
 */
Breadcrumbs::for('home', static function (Generator $trail) {
    $trail->push(__('breadcrumbs.home.keystone_guru'), route('home'));
});

/**
 * Site pages
 */

Breadcrumbs::for('dungeonroute.new', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.dungeonroute.new'), route('dungeonroute.new'));
});
Breadcrumbs::for('misc.affixes', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.affixes'), route('misc.affixes'));
});
Breadcrumbs::for('misc.changelog', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.changelog'), route('misc.changelog'));
});

/**
 * Explore page
 */
Breadcrumbs::for('dungeon.explore.list', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.dungeon.explore'), route('dungeon.explore.list'));
});

/**
 * Routes page
 */
Breadcrumbs::for('dungeonroutes', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.routes'), route('dungeonroutes'));
});
Breadcrumbs::for('dungeonroutes.expansion', static function (Generator $trail, Expansion $expansion) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.routes_expansion', ['expansion' => __($expansion->name)]), route('dungeonroutes.expansion', ['expansion' => $expansion]));
});
Breadcrumbs::for('dungeonroute.discover.search', static function (Generator $trail) {
    $trail->parent('dungeonroutes');
    $trail->push(__('breadcrumbs.home.dungeonroutes.search'), route('dungeonroutes.search'));
});

/**
 * Season
 */
Breadcrumbs::for('dungeonroutes.season', static function (Generator $trail, Expansion $expansion, Season $season) {
    $trail->parent('dungeonroutes.expansion', $expansion);
    $trail->push(__('breadcrumbs.home.dungeonroutes.routes_season', ['season' => $season->index]), route('dungeonroutes.season', ['expansion' => $expansion, 'season' => $season->index]));
});
Breadcrumbs::for('dungeonroutes.season.popular', static function (Generator $trail, Expansion $expansion, Season $season) {
    $trail->parent('dungeonroutes.season', $expansion, $season);
    $trail->push(__('breadcrumbs.home.dungeonroutes.season.popular'), route('dungeonroutes.season.popular', ['expansion' => $expansion, 'season' => $season->index]));
});

Breadcrumbs::for('dungeonroutes.season.nextweek', static function (Generator $trail, Expansion $expansion, Season $season) {
    $trail->parent('dungeonroutes.season', $expansion, $season);
    $trail->push(__('breadcrumbs.home.dungeonroutes.season.next_week_affixes'), route('dungeonroutes.season.nextweek', ['expansion' => $expansion, 'season' => $season->index]));
});

Breadcrumbs::for('dungeonroutes.season.thisweek', static function (Generator $trail, Expansion $expansion, Season $season) {
    $trail->parent('dungeonroutes.season', $expansion, $season);
    $trail->push(__('breadcrumbs.home.dungeonroutes.season.this_week_affixes'), route('dungeonroutes.season.thisweek', ['expansion' => $expansion, 'season' => $season->index]));
});

Breadcrumbs::for('dungeonroutes.season.new', static function (Generator $trail, Expansion $expansion, Season $season) {
    $trail->parent('dungeonroutes.season', $expansion, $season);
    $trail->push(__('breadcrumbs.home.dungeonroutes.season.new'), route('dungeonroutes.season.new', ['expansion' => $expansion, 'season' => $season->index]));
});

/**
 * General categories
 */
Breadcrumbs::for('dungeonroutes.popular', static function (Generator $trail, Expansion $expansion) {
    $trail->parent('dungeonroutes.expansion', $expansion);
    $trail->push(__('breadcrumbs.home.dungeonroutes.popular'), route('dungeonroutes.popular', ['expansion' => $expansion]));
});

Breadcrumbs::for('dungeonroutes.nextweek', static function (Generator $trail, Expansion $expansion) {
    $trail->parent('dungeonroutes.expansion', $expansion);
    $trail->push(__('breadcrumbs.home.dungeonroutes.next_week_affixes'), route('dungeonroutes.nextweek', ['expansion' => $expansion]));
});

Breadcrumbs::for('dungeonroutes.thisweek', static function (Generator $trail, Expansion $expansion) {
    $trail->parent('dungeonroutes.expansion', $expansion);
    $trail->push(__('breadcrumbs.home.dungeonroutes.this_week_affixes'), route('dungeonroutes.thisweek', ['expansion' => $expansion]));
});

Breadcrumbs::for('dungeonroutes.new', static function (Generator $trail, Expansion $expansion) {
    $trail->parent('dungeonroutes.expansion', $expansion);
    $trail->push(__('breadcrumbs.home.dungeonroutes.new'), route('dungeonroutes.new', ['expansion' => $expansion]));
});

/**
 * General for a dungeon
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon', static function (Generator $trail, Dungeon $dungeon) {
    $trail->parent('dungeonroutes.expansion', $dungeon->expansion);
    $trail->push(__($dungeon->name), route('dungeonroutes.discoverdungeon', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon]));
});

/**
 * Dungeon categories
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon.popular', static function (Generator $trail, Dungeon $dungeon) {
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.popular'), route('dungeonroutes.discoverdungeon.popular', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.nextweek', static function (Generator $trail, Dungeon $dungeon) {
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.next_week_affixes'), route('dungeonroutes.discoverdungeon.nextweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.thisweek', static function (Generator $trail, Dungeon $dungeon) {
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.this_week_affixes'), route('dungeonroutes.discoverdungeon.thisweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.new', static function (Generator $trail, Dungeon $dungeon) {
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('breadcrumbs.home.dungeonroutes.new'), route('dungeonroutes.discoverdungeon.new', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon]));
});

/**
 * User profile pages
 */
Breadcrumbs::for('profile.edit', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.my_profile'), route('profile.edit'));
});

Breadcrumbs::for('profile.overview', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.overview'), route('home'));
});

Breadcrumbs::for('profile.routes', static function (Generator $trail) {
    $trail->parent('profile.edit');
    $trail->push(__('breadcrumbs.home.my_routes'), route('profile.routes'));
});

Breadcrumbs::for('profile.tags', static function (Generator $trail) {
    $trail->parent('profile.edit');
    $trail->push(__('breadcrumbs.home.my_tags'), route('profile.tags'));
});

/**
 * Teams pages
 */
Breadcrumbs::for('team.list', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.my_teams'), route('team.list'));
});

Breadcrumbs::for('team.edit', static function (Generator $trail, Team $team) {
    $trail->parent('team.list');
    if ($team === null) {
        $trail->push(__('breadcrumbs.home.new_team'), route('team.new'));
    } else {
        $trail->push(__('breadcrumbs.home.edit_team'), route('team.edit', $team));
    }
});

Breadcrumbs::for('team.invite', static function (Generator $trail, Team $team) {
    $trail->parent('team.list');
    $trail->push(__('breadcrumbs.home.join_team'), route('team.invite', $team));
});

/**
 * Admin pages
 */
Breadcrumbs::for('admin', static function (Generator $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.home.admin.admin'));
});

// Tools
Breadcrumbs::for('admin.tools.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.tools.admin_tools'), route('admin.tools'));
});
Breadcrumbs::for('admin.tools.datadump.viewexporteddungeondata', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_exported_dungeondata'), route('admin.tools.datadump.exportdungeondata'));
});
Breadcrumbs::for('admin.tools.datadump.viewexportedrelease', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_exported_releases'), route('admin.tools.datadump.exportreleases'));
});
Breadcrumbs::for('admin.tools.exception.select', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.select_exception'), route('admin.tools.exception.select'));
});
Breadcrumbs::for('admin.tools.mdt.diff', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.mdt_diff'), route('admin.tools.mdt.diff'));
});
Breadcrumbs::for('admin.tools.mdt.string', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.view_mdt_string_contents'), route('admin.tools.mdt.dungeonroute.viewasstring'));
});
Breadcrumbs::for('admin.tools.npc.import', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.import_npcs'), route('admin.tools.npc.import'));
});

// Releases
Breadcrumbs::for('admin.release.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.releases'), route('admin.releases'));
});
Breadcrumbs::for('admin.release.edit', static function (Generator $trail, ?Release $release) {
    $trail->parent('admin.release.list');
    if ($release === null) {
        $trail->push(__('breadcrumbs.home.admin.new_release'), route('admin.release.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.edit_release'), route('admin.release.edit', $release));
    }
});

// Expansions
Breadcrumbs::for('admin.expansion.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.expansions.expansions'), route('admin.expansions'));
});
Breadcrumbs::for('admin.expansion.edit', static function (Generator $trail, ?Expansion $expansion) {
    $trail->parent('admin.expansion.list');
    if ($expansion === null) {
        $trail->push(__('breadcrumbs.home.admin.expansions.new_expansion'), route('admin.expansion.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.expansions.edit_expansion'), route('admin.expansion.edit', $expansion));
    }
});

// Dungeons
Breadcrumbs::for('admin.dungeon.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.dungeons.dungeons'), route('admin.dungeons'));
});
Breadcrumbs::for('admin.dungeon.edit', static function (Generator $trail, ?Dungeon $dungeon) {
    $trail->parent('admin.dungeon.list');
    if ($dungeon === null) {
        $trail->push(__('breadcrumbs.home.admin.dungeons.new_dungeon'), route('admin.dungeon.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.dungeons.edit_dungeon', ['dungeon' => __($dungeon->name)]), route('admin.dungeon.edit', $dungeon));
    }
});
Breadcrumbs::for('admin.floor.edit', static function (Generator $trail, Dungeon $dungeon, ?Floor $floor) {
    $trail->parent('admin.dungeon.edit', $dungeon);
    if ($floor === null) {
        $trail->push(__('breadcrumbs.home.admin.floors.new_floor'), route('admin.floor.new', ['dungeon' => $dungeon]));
    } else {
        $trail->push(__('breadcrumbs.home.admin.floors.edit_floor'), route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $floor]));
    }
});
Breadcrumbs::for('admin.dungeonspeedrunrequirednpc.new', static function (Generator $trail, Dungeon $dungeon, Floor $floor, int $difficulty) {
    $trail->parent('admin.floor.edit', $dungeon, $floor);
    $trail->push(
        $difficulty === \App\Models\Dungeon::DIFFICULTY_10_MAN ?
            __('breadcrumbs.home.admin.dungeonspeedrunrequirednpc.new_dungeonspeedrunrequirednpc10man') :
            __('breadcrumbs.home.admin.dungeonspeedrunrequirednpc.new_dungeonspeedrunrequirednpc25man'),
        route('admin.dungeonspeedrunrequirednpc.new', ['dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty]));
});

// Npcs
Breadcrumbs::for('admin.npc.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.npcs.npcs'), route('admin.npcs'));
});
Breadcrumbs::for('admin.npc.edit', static function (Generator $trail, ?Npc $npc) {
    $trail->parent('admin.npc.list');
    if ($npc === null) {
        $trail->push(__('breadcrumbs.home.admin.npcs.new_npc'), route('admin.npc.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.npcs.edit_npc'), route('admin.npc.edit', $npc));
    }
});

// Npc enemy forces
Breadcrumbs::for('admin.npcenemyforces.edit', static function (Generator $trail, Npc $npc, NpcEnemyForces $npcEnemyForces) {
    $trail->parent('admin.npc.edit', $npc);
    $trail->push(
        __('breadcrumbs.home.admin.npcenemyforces.edit_npc_enemy_forces'),
        route('admin.npcenemyforces.edit', ['npc' => $npc, 'npcEnemyForces' => $npcEnemyForces])
    );
});

// Spells
Breadcrumbs::for('admin.spell.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.spells.spells'), route('admin.spells'));
});
Breadcrumbs::for('admin.spell.edit', static function (Generator $trail, ?Spell $spell) {
    $trail->parent('admin.spell.list');
    if ($spell === null) {
        $trail->push(__('breadcrumbs.home.admin.spells.new_spell'), route('admin.spell.new'));
    } else {
        $trail->push(__('breadcrumbs.home.admin.spells.edit_spell'), route('admin.spell.edit', $spell));
    }
});

// Users
Breadcrumbs::for('admin.user.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.users.users'), route('admin.users'));
});

// User reports
Breadcrumbs::for('admin.userreport.list', static function (Generator $trail) {
    $trail->parent('admin');
    $trail->push(__('breadcrumbs.home.admin.user_reports.user_reports'), route('admin.userreports'));
});
