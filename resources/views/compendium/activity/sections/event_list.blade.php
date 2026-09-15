<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> $events
 * @var string                                                 $date           Y-m-d string
 * @var Dungeon                                                $contextDungeon
 * @var int|null                                               $limit          Optional row cap, passed through to the shared event list
 */
?>
@include('compendium.sections.event_list', [
    'events'           => $events,
    'emptyKey'         => 'view_compendium.activity.day.empty',
    'showNpcSubject'   => true,
    'showSpellSubject' => true,
    'contextDungeon'   => $contextDungeon,
    'date'             => $date,
    'limit'            => $limit ?? null,
])
