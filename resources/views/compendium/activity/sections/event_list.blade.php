<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var Collection<CombatLogNpcEvent|CombatLogSpellEvent> $events
 * @var string                                            $date Y-m-d string
 * @var Dungeon                                           $contextDungeon
 */
?>
@include('compendium.sections.event_list', [
    'events'           => $events,
    'emptyKey'         => 'view_compendium.activity.day.empty',
    'showNpcSubject'   => true,
    'showSpellSubject' => true,
    'contextDungeon'   => $contextDungeon,
    'date'             => $date,
])
