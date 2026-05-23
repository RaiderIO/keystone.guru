<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use Illuminate\Support\Collection;

/**
 * @var Collection<CombatLogNpcEvent|CombatLogSpellEvent> $eventFeed
 */

$eventBadgeClass = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    if ($event instanceof CombatLogNpcEvent) {
        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded   => 'success',
            CombatLogNpcEventType::CharacteristicRemoved => 'danger',
            CombatLogNpcEventType::SpellAssigned         => 'info',
        };
    }

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated    => 'info',
        CombatLogSpellEventType::PropertyChanged => 'warning',
        CombatLogSpellEventType::PropertyRemoved => 'danger',
    };
};

$eventIcon = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): string {
    if ($event instanceof CombatLogNpcEvent) {
        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded   => 'fas fa-plus',
            CombatLogNpcEventType::CharacteristicRemoved => 'fas fa-minus',
            CombatLogNpcEventType::SpellAssigned         => 'fas fa-bolt',
        };
    }

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated    => 'fas fa-plus',
        CombatLogSpellEventType::PropertyChanged => 'fas fa-arrow-up',
        CombatLogSpellEventType::PropertyRemoved => 'fas fa-times',
    };
};

$spellPropertyName = static function (SpellProperty $property): string {
    if ($property === SpellProperty::Aura || $property === SpellProperty::Debuff) {
        return __('view_compendium.npc.sections.event_feed.event.property.' . $property->value);
    }

    // miss_reflect → reflect
    return __('spellmisstypes.' . substr($property->value, 5));
};

$eventDescription = static function (CombatLogNpcEvent|CombatLogSpellEvent $event) use ($spellPropertyName): string {
    if ($event instanceof CombatLogNpcEvent) {
        $modelName = $event->model ? __($event->model->name) : sprintf('#%d', $event->model_id);

        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded   => __('view_compendium.npc.sections.event_feed.event.characteristic_added', ['name' => $modelName]),
            CombatLogNpcEventType::CharacteristicRemoved => __('view_compendium.npc.sections.event_feed.event.characteristic_removed', ['name' => $modelName]),
            CombatLogNpcEventType::SpellAssigned         => __('view_compendium.npc.sections.event_feed.event.spell_assigned', ['name' => $modelName]),
        };
    }

    $spellName = $event->spell ? __($event->spell->name) : sprintf('#%d', $event->spell_id);

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated    => __('view_compendium.npc.sections.event_feed.event.spell_created', ['spell' => $spellName]),
        CombatLogSpellEventType::PropertyChanged => __('view_compendium.npc.sections.event_feed.event.property_changed', ['spell' => $spellName, 'property' => $spellPropertyName($event->property)]),
        CombatLogSpellEventType::PropertyRemoved => __('view_compendium.npc.sections.event_feed.event.property_removed', ['spell' => $spellName, 'property' => $spellPropertyName($event->property)]),
    };
};
?>
{{-- Event Feed --}}
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.npc.sections.event_feed.title') }}</h4>
        @if($eventFeed->isNotEmpty())
            <ul class="list-unstyled mb-0">
                @foreach($eventFeed as $event)
                    <?php /** @var CombatLogNpcEvent|CombatLogSpellEvent $event */ ?>
                    <li class="d-flex align-items-start mb-2">
                        <span class="badge badge-{{ $eventBadgeClass($event) }} mr-2 mt-1" style="min-width: 20px;">
                            <i class="{{ $eventIcon($event) }}"></i>
                        </span>
                        <div>
                            <span>{{ $eventDescription($event) }}</span>
                            <small class="text-muted ml-1">— {{ $event->created_at->diffForHumans() }}</small>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">{{ __('view_compendium.npc.sections.event_feed.empty') }}</p>
        @endif
    </div>
</div>
