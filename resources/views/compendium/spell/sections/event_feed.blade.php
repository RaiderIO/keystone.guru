<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use Illuminate\Support\Collection;

/**
 * @var Collection<CombatLogNpcEvent|CombatLogSpellEvent> $eventFeed
 */
?>
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.spell.sections.event_feed.title') }}</h4>
        @include('compendium.sections.event_list', [
            'events'         => $eventFeed,
            'emptyKey'       => 'view_compendium.spell.sections.event_feed.empty',
            'showNpcSubject' => true,
        ])
    </div>
</div>
