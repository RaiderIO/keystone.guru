<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

/**
 * @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> $events
 * @var string                                                 $emptyKey         Translation key for the empty-state message
 * @var bool                                                   $showNpcSubject   Whether to render the NPC icon+name before the description on NPC events
 * @var bool                                                   $showSpellSubject Whether to render the Spell icon+name before the description on Spell events
 * @var Dungeon|null                                           $contextDungeon   When set, the timestamp becomes a link to the activity day page
 * @var string|null                                            $date             Y-m-d date string, required when $contextDungeon is set
 */

$showNpcSubject   ??= false;
$showSpellSubject ??= false;
$contextDungeon   ??= null;
$date             ??= null;

$shouldShowSubject = static function (CombatLogNpcEvent|CombatLogSpellEvent $event) use (
    $showNpcSubject,
    $showSpellSubject
): bool {
    return $event instanceof CombatLogNpcEvent ? $showNpcSubject : $showSpellSubject;
};

$eventTypeIcon = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    return $event instanceof CombatLogNpcEvent ? 'fas fa-dragon' : 'fas fa-magic';
};

$eventBadgeClass = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    if ($event instanceof CombatLogNpcEvent) {
        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded => 'success',
            CombatLogNpcEventType::CharacteristicRemoved => 'danger',
            CombatLogNpcEventType::SpellAssigned => 'info',
        };
    }

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated => 'info',
        CombatLogSpellEventType::PropertyChanged => 'warning',
        CombatLogSpellEventType::PropertyRemoved => 'danger',
    };
};

$eventIcon = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    if ($event instanceof CombatLogNpcEvent) {
        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded => 'fas fa-plus',
            CombatLogNpcEventType::CharacteristicRemoved => 'fas fa-minus',
            CombatLogNpcEventType::SpellAssigned => 'fas fa-bolt',
        };
    }

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated => 'fas fa-plus',
        CombatLogSpellEventType::PropertyChanged => 'fas fa-arrow-up',
        CombatLogSpellEventType::PropertyRemoved => 'fas fa-times',
    };
};

$spellPropertyName = static function (SpellProperty $property): string {
    if ($property === SpellProperty::Aura || $property === SpellProperty::Debuff) {
        return __('view_compendium.event.property.' . $property->value);
    }

    // miss_reflect → reflect
    return __('spellmisstypes.' . substr($property->value, 5));
};

$eventDescription = static function (CombatLogNpcEvent|CombatLogSpellEvent $event) use ($spellPropertyName): string {
    if ($event instanceof CombatLogNpcEvent) {
        $modelName = $event->model ? __($event->model->getAttribute('name')) : sprintf('#%d', $event->model_id);

        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded => __('view_compendium.event.characteristic_added', ['name' => $modelName]),
            CombatLogNpcEventType::CharacteristicRemoved => __('view_compendium.event.characteristic_removed', ['name' => $modelName]),
            CombatLogNpcEventType::SpellAssigned => __('view_compendium.event.spell_assigned', ['name' => $modelName]),
        };
    }

    $spellName = $event->spell ? __($event->spell->name) : sprintf('#%d', $event->spell_id);

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated => __('view_compendium.event.spell_created', ['spell' => $spellName]),
        CombatLogSpellEventType::PropertyChanged => __('view_compendium.event.property_changed', [
            'spell'    => $spellName,
            'property' => $spellPropertyName($event->property)
        ]),
        CombatLogSpellEventType::PropertyRemoved => __('view_compendium.event.property_removed', [
            'spell'    => $spellName,
            'property' => $spellPropertyName($event->property)
        ]),
    };
};

$eventAnchorId = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    return $event instanceof CombatLogNpcEvent
        ? sprintf('npc-event-%d', $event->id)
        : sprintf('spell-event-%d', $event->id);
};

$eventSubjectHtml = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    if ($event instanceof CombatLogNpcEvent) {
        /** @var Npc|null $npc */
        $npc = $event->getRelation('npc');

        if (!$npc) {
            return sprintf('#%d', $event->npc_id);
        }

        return view('common.npc.link', ['npc' => $npc])->render();
    }

    /** @var Spell|null $spell */
    $spell = $event->getRelation('spell');

    return $spell ? view('common.spell.link', ['spell' => $spell])->render() : '';
};
?>
@isset($date)
    <h4 id="day-{{ $date }}">
        <a href="{{ route('compendium.activity.day', ['dungeon' => $contextDungeon, 'date' => $date]) }}">
            {{ Carbon::parse($date)->format('F j, Y') }}
        </a>
    </h4>
@endisset

@if($events->isNotEmpty())
    <ul class="list-unstyled mb-0">
        @foreach($events as $event)
                <?php /** @var CombatLogNpcEvent|CombatLogSpellEvent $event */ ?>
                <?php $anchorId = $eventAnchorId($event); ?>
            <li @if($contextDungeon) id="{{ $anchorId }}" @endif class="d-flex align-items-start mb-2">
                <span class="text-muted me-1" style="min-width: 14px; text-align: center;">
                    <i class="{{ $eventTypeIcon($event) }}" style="font-size: .75rem;"></i>
                </span>
                <span class="badge text-bg-{{ $eventBadgeClass($event) }} me-2 mt-1" style="min-width: 20px;">
                    <i class="{{ $eventIcon($event) }}"></i>
                </span>
                <div>
                    @if($shouldShowSubject($event))
                        <span class="me-1">{!! $eventSubjectHtml($event) !!}</span>
                    @endif
                    <span>{{ $eventDescription($event) }}</span>
                    <small class="text-muted ms-1">
                        -
                        @if($contextDungeon)
                            <a href="{{ route('compendium.activity.day', ['dungeon' => $contextDungeon, 'date' => $date]) }}#{{ $anchorId }}"
                               class="text-muted">
                                {{ $event->created_at->diffForHumans() }}
                            </a>
                        @else
                            {{ $event->created_at->diffForHumans() }}
                        @endif
                    </small>
                </div>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted">{{ __($emptyKey) }}</p>
@endif
