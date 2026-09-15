<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use Illuminate\Support\Collection;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 *
 * @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> $eventFeed
 */
?>
@include('compendium.sections.event_list', [
    'events'   => $eventFeed,
    'emptyKey' => 'view_compendium.npc.sections.event_feed.empty',
    'showSpellSubject' => true,
])
