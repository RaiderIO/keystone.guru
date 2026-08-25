<?php

use App\Models\Dungeon;
use App\Models\Spell\SpellTuningChange;
use App\Models\Spell\SpellTuningChangeType;
use Illuminate\Support\Collection;

/**
 * Shared primitive that renders a list of spell tuning changes - used by the per-spell "Tuning changes"
 * section (no subject, no dungeons) and the Compendium-wide per-build page (with both).
 *
 * Each row reads as one of:
 *  - "Damage: 29,095 -> 38,793 (+33%)"          a scalable value both builds rendered a number for
 *  - "Damage coefficient: 18 -> 17 (-5.6%)"      a scalable value without a rendered number (no multiplier)
 *  - "Duration: 10 sec -> 25 sec"                any other kind of value
 *  - "Description rewritten" + old/new text       the numbers no longer line up between the builds
 *
 * @var Collection<int, SpellTuningChange> $changes
 * @var string                             $emptyKey         Translation key for the empty-state message
 * @var bool                               $showSpellSubject Whether to lead each row with the spell's icon+name
 * @var bool                               $showDungeons     Whether to follow each row with the spell's dungeons
 */

$showSpellSubject ??= false;
$showDungeons     ??= false;

/**
 * Percent with an explicit sign and no more precision than the change warrants: +33.3%, -50%, +5%.
 */
$formatDelta = static function (float $deltaPercent): string {
    $rounded = round($deltaPercent, 1);

    return sprintf('%s%s%%', $rounded > 0 ? '+' : '', rtrim(rtrim(number_format($rounded, 1), '0'), '.'));
};

$formatCoefficient = static fn(?float $coefficient): string => $coefficient === null ? '-' : rtrim(rtrim(number_format($coefficient, 2), '0'), '.');

/*
 * WoW ships duplicate spell records per NPC whose numbers move in lockstep, so a build routinely
 * carries pairs of same-named, same-values changes. Rendered as-is they read as a bug; collapse
 * them into one row with a xN marker. Only the subject list can collide - the per-spell section
 * shows one spell's own rows and keeps them all.
 */
$rows = $changes
    ->groupBy(static fn(SpellTuningChange $change): string => $showSpellSubject
        ? implode('|', [
            __($change->spell->name),
            $change->change_type->value,
            $change->kind->value ?? '',
            $change->old_text ?? (string)$change->old_coefficient,
            $change->new_text ?? (string)$change->new_coefficient,
            (string)$change->delta,
        ])
        : (string)$change->id)
    ->map(static fn(Collection $group): array => ['change' => $group->first(), 'count' => $group->count()])
    ->values();
?>
@if($changes->isEmpty())
    <p class="text-muted mb-0">{{ __($emptyKey) }}</p>
@else
    <ul class="compendium_tuning_list list-unstyled mb-0 {{ $showSpellSubject ? 'compendium_tuning_list--subject' : '' }}">
        @foreach($rows as $row)
            <?php
            /** @var SpellTuningChange $change */
            $change = $row['change'];
            ?>
            <li class="compendium_tuning_row">
                @if($showSpellSubject)
                    <span class="compendium_tuning_subject">
                        @include('common.spell.link', ['spell' => $change->spell])
                        @if($row['count'] > 1)
                            <span class="compendium_tuning_count"
                                  title="{{ __('view_compendium.sections.tuning_change_list.shared_by', ['count' => $row['count']]) }}">&times;{{ $row['count'] }}</span>
                        @endif
                    </span>
                @endif

                <span class="compendium_tuning_body">
                    @if($change->change_type === SpellTuningChangeType::DescriptionRewritten)
                        @php($descriptionAdded = $change->old_text === null)
                        <span class="compendium_tuning_kind">
                            {{ __($descriptionAdded ? 'view_compendium.sections.tuning_change_list.added' : 'view_compendium.sections.tuning_change_list.rewritten') }}
                        </span>
                        <span class="compendium_tuning_values compendium_tuning_values--stacked">
                            @if(!$descriptionAdded)
                                <span class="compendium_tuning_old">{{ $change->old_text }}</span>
                                <span class="visually-hidden">{{ __('view_compendium.sections.tuning_change_list.changed_to') }}</span>
                            @endif
                            <span class="compendium_tuning_new">{{ $change->new_text ?? __('view_compendium.sections.tuning_change_list.no_description') }}</span>
                        </span>
                    @elseif($change->isScalable())
                        @php($hasTexts = $change->hasRenderedTexts())
                        <span class="compendium_tuning_kind">
                            {{ __(sprintf('view_compendium.sections.tuning_change_list.kinds.%s', $change->kind->value)) }}@if(!$hasTexts) {{ __('view_compendium.sections.tuning_change_list.coefficient') }}@endif
                        </span>
                        <span class="compendium_tuning_values">
                            <span class="compendium_tuning_old">{{ $hasTexts ? $change->old_text : $formatCoefficient($change->old_coefficient) }}</span>
                            <i class="fas fa-arrow-right compendium_tuning_arrow" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ __('view_compendium.sections.tuning_change_list.changed_to') }}</span>
                            <span class="compendium_tuning_new">{{ $hasTexts ? $change->new_text : $formatCoefficient($change->new_coefficient) }}</span>
                        </span>
                    @else
                        <span class="compendium_tuning_kind">
                            {{ __(sprintf('view_compendium.sections.tuning_change_list.kinds.%s', $change->kind->value)) }}
                        </span>
                        <span class="compendium_tuning_values">
                            <span class="compendium_tuning_old">{{ $change->old_text !== '' ? $change->old_text : '-' }}</span>
                            <i class="fas fa-arrow-right compendium_tuning_arrow" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ __('view_compendium.sections.tuning_change_list.changed_to') }}</span>
                            <span class="compendium_tuning_new">{{ $change->new_text !== '' ? $change->new_text : '-' }}</span>
                        </span>
                    @endif

                    @if($showDungeons && $change->spell->dungeons->isNotEmpty())
                        <span class="compendium_tuning_dungeons compendium_identity_meta">
                            @foreach($change->spell->dungeons as $dungeon)
                                <?php /** @var Dungeon $dungeon */ ?>
                                <span class="compendium_chip">{{ __($dungeon->name) }}</span>
                            @endforeach
                        </span>
                    @endif
                </span>

                @if($change->getDeltaPercent() !== null)
                    <span class="compendium_tuning_delta {{ $change->delta > 0 ? 'compendium_tuning_delta--up' : 'compendium_tuning_delta--down' }}">
                        ({{ $formatDelta($change->getDeltaPercent()) }})
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
