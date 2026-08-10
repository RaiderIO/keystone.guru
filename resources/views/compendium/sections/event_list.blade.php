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
 * @var int|null                                               $limit            Cap the rendered rows; the remainder collapses into an "and :count more" link to the day page
 */

$showNpcSubject   ??= false;
$showSpellSubject ??= false;
$contextDungeon   ??= null;
$date             ??= null;
$limit            ??= null;

$hiddenCount    = $limit !== null ? max(0, $events->count() - $limit) : 0;
$displayedEvents = $hiddenCount > 0 ? $events->take($limit) : $events;

$shouldShowSubject = (static fn(CombatLogNpcEvent|CombatLogSpellEvent $event): bool => $event instanceof CombatLogNpcEvent ? $showNpcSubject : $showSpellSubject);

/**
 * One colored icon per event type carries the add/change/remove semantics in the log's icon
 * column (the old badge-block + type-icon pair collapsed into a single glyph).
 *
 * @return array{icon: string, color: string}
 */
$eventGlyph = static function (CombatLogNpcEvent|CombatLogSpellEvent $event): array {
    if ($event instanceof CombatLogNpcEvent) {
        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded => ['icon' => 'fas fa-plus', 'color' => 'text-success'],
            CombatLogNpcEventType::CharacteristicRemoved => ['icon' => 'fas fa-minus', 'color' => 'text-danger'],
            CombatLogNpcEventType::SpellAssigned => ['icon' => 'fas fa-bolt', 'color' => 'text-info'],
        };
    }

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated => ['icon' => 'fas fa-plus', 'color' => 'text-info'],
        CombatLogSpellEventType::PropertyChanged => ['icon' => 'fas fa-arrow-up', 'color' => 'text-warning'],
        CombatLogSpellEventType::PropertyRemoved => ['icon' => 'fas fa-times', 'color' => 'text-danger'],
        CombatLogSpellEventType::SchoolRecorded => ['icon' => 'fas fa-plus', 'color' => 'text-info'],
    };
};

$spellPropertyName = static function (SpellProperty $property): string {
    if ($property === SpellProperty::Aura || $property === SpellProperty::Debuff) {
        return __('view_compendium.event.property.' . $property->value);
    }

    if ($property->isCounter()) {
        // counter_vanish → vanish
        return __('spellcounters.' . substr($property->value, 8));
    }

    if ($property->isImmunityBypass()) {
        // bypass_divine_shield → divine_shield
        return __('spellimmunities.' . substr($property->value, 7));
    }

    // miss_reflect → reflect
    return __('spellmisstypes.' . substr($property->value, 5));
};

/**
 * Returns HTML: spell names inside descriptions render as links to their compendium page, so
 * every interpolated plain string is escaped here and the caller prints with {!! !!}.
 * When the row already leads with the spell as its subject link ($subjectShown), the
 * description switches to the subject-less wording instead of repeating the spell name.
 */
$eventDescription = static function (CombatLogNpcEvent|CombatLogSpellEvent $event, bool $subjectShown) use ($spellPropertyName): string {
    if ($event instanceof CombatLogNpcEvent) {
        $model = $event->model;

        // An assigned spell has its own compendium page - render the name as a spell link
        $modelName = $model instanceof Spell
            ? view('common.spell.link', ['spell' => $model])->render()
            : e($model ? __($model->getAttribute('name')) : sprintf('#%d', $event->model_id));

        return match ($event->event_type) {
            CombatLogNpcEventType::CharacteristicAdded => __('view_compendium.event.characteristic_added', ['name' => $modelName]),
            CombatLogNpcEventType::CharacteristicRemoved => __('view_compendium.event.characteristic_removed', ['name' => $modelName]),
            CombatLogNpcEventType::SpellAssigned => __('view_compendium.event.spell_assigned', ['name' => $modelName]),
        };
    }

    $spellName = e($event->spell ? __($event->spell->name) : sprintf('#%d', $event->spell_id));

    // Counters and immunity bypasses are properties of what a *player* can do about the spell, so the generic
    // "Affected by :property" wording does not fit them
    [$addedKey, $removedKey] = match (true) {
        $event->property?->isCounter() === true => $subjectShown ? [
            'view_compendium.event.counter_added_no_subject',
            'view_compendium.event.counter_removed_no_subject',
        ] : [
            'view_compendium.event.counter_added',
            'view_compendium.event.counter_removed',
        ],
        $event->property?->isImmunityBypass() === true => $subjectShown ? [
            'view_compendium.event.immunity_bypass_added_no_subject',
            'view_compendium.event.immunity_bypass_removed_no_subject',
        ] : [
            'view_compendium.event.immunity_bypass_added',
            'view_compendium.event.immunity_bypass_removed',
        ],
        default => [
            'view_compendium.event.property_changed',
            'view_compendium.event.property_removed',
        ],
    };

    return match ($event->event_type) {
        CombatLogSpellEventType::SpellCreated => __(
            $subjectShown ? 'view_compendium.event.spell_created_no_subject' : 'view_compendium.event.spell_created',
            ['spell' => $spellName]
        ),
        CombatLogSpellEventType::PropertyChanged => __($addedKey, [
            'spell'    => $spellName,
            'property' => e($spellPropertyName($event->property))
        ]),
        CombatLogSpellEventType::PropertyRemoved => __($removedKey, [
            'spell'    => $spellName,
            'property' => e($spellPropertyName($event->property))
        ]),
        CombatLogSpellEventType::SchoolRecorded => __(
            $subjectShown ? 'view_compendium.event.school_recorded_no_subject' : 'view_compendium.event.school_recorded',
            [
                'spell'   => $spellName,
                'schools' => e($event->spell
                    ? Spell::maskToReadableString(Spell::ALL_SCHOOLS, $event->spell->schools_mask, 'spellschools')
                    : '-'),
            ]
        ),
    };
};

$eventAnchorId = (static fn(CombatLogNpcEvent|CombatLogSpellEvent $event): string => $event instanceof CombatLogNpcEvent
    ? sprintf('npc-event-%d', $event->id)
    : sprintf('spell-event-%d', $event->id));

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
    <div class="compendium_log_day" id="day-{{ $date }}">
        <h4 class="compendium_log_day_title">
            <a href="{{ route('compendium.activity.day', ['dungeon' => $contextDungeon, 'date' => $date]) }}">
                {{ Carbon::parse($date)->format('F j, Y') }}
            </a>
        </h4>
        <span class="compendium_log_day_count">
            {{ trans_choice('view_compendium.event.count', $events->count()) }}
        </span>
    </div>
@endisset

@if($events->isNotEmpty())
    <div>
        @foreach($displayedEvents as $event)
                <?php /** @var CombatLogNpcEvent|CombatLogSpellEvent $event */ ?>
                <?php $anchorId = $eventAnchorId($event); ?>
                <?php $glyph = $eventGlyph($event); ?>
                <?php $subjectHtml = $shouldShowSubject($event) ? $eventSubjectHtml($event) : ''; ?>
            <div @if($contextDungeon) id="{{ $anchorId }}" @endif class="compendium_log_row">
                <span class="compendium_log_time">
                    @if($contextDungeon)
                        <a href="{{ route('compendium.activity.day', ['dungeon' => $contextDungeon, 'date' => $date]) }}#{{ $anchorId }}">
                            {{ $event->created_at->diffForHumans() }}
                        </a>
                    @else
                        {{ $event->created_at->diffForHumans() }}
                    @endif
                </span>
                <span class="compendium_log_icon {{ $glyph['color'] }}">
                    <i class="{{ $glyph['icon'] }}"></i>
                </span>
                <span class="compendium_log_text">
                    @if($subjectHtml !== '')
                        <span class="me-1">{!! $subjectHtml !!}</span>
                    @endif
                    <span>{!! $eventDescription($event, $subjectHtml !== '') !!}</span>
                </span>
            </div>
        @endforeach
        @if($hiddenCount > 0 && $contextDungeon !== null && $date !== null)
            <div class="compendium_log_row">
                <span class="compendium_log_time"></span>
                <span class="compendium_log_icon text-muted"><i class="fas fa-ellipsis-h"></i></span>
                <span class="compendium_log_text">
                    <a href="{{ route('compendium.activity.day', ['dungeon' => $contextDungeon, 'date' => $date]) }}">
                        {{ __('view_compendium.event.more', ['count' => $hiddenCount]) }}
                    </a>
                </span>
            </div>
        @endif
    </div>
@else
    <p class="text-muted">{{ __($emptyKey) }}</p>
@endif
