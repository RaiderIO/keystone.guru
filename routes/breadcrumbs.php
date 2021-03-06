<?php

use App\Models\Dungeon;
use Diglactic\Breadcrumbs\Breadcrumbs;

/**
 * Main page
 */
Breadcrumbs::for('dungeonroutes', function ($trail)
{
    $trail->push(__('Routes'), route('dungeonroutes'));
});

/**
 * General categories
 */
Breadcrumbs::for('dungeonroutes.popular', function ($trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('Popular'), route('dungeonroutes.popular'));
});

Breadcrumbs::for('dungeonroutes.nextweek', function ($trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('Next week\'s affixes'), route('dungeonroutes.nextweek'));
});

Breadcrumbs::for('dungeonroutes.thisweek', function ($trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('This week\'s affixes'), route('dungeonroutes.thisweek'));
});

Breadcrumbs::for('dungeonroutes.new', function ($trail)
{
    $trail->parent('dungeonroutes');
    $trail->push(__('New'), route('dungeonroutes.new'));
});


/**
 * General for a dungeon
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon', function ($trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes');
    $trail->push($dungeon->name, route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon]));
});

/**
 * Dungeon categories
 */
Breadcrumbs::for('dungeonroutes.discoverdungeon.popular', function ($trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('Popular'), route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.nextweek', function ($trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('Next week\'s affixes'), route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.thisweek', function ($trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('This week\'s affixes'), route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon]));
});

Breadcrumbs::for('dungeonroutes.discoverdungeon.new', function ($trail, Dungeon $dungeon)
{
    $trail->parent('dungeonroutes.discoverdungeon', $dungeon);
    $trail->push(__('New'), route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon]));
});