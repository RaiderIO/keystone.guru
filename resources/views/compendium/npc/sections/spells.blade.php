<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 *
 * @var Npc $npc
 */
?>
@if($npc->spells->isNotEmpty())
    <div class="table-responsive">
        <table class="compendium_table">
            <thead>
            <tr>
                <th>{{ __('view_compendium.npc.sections.spells.header_name') }}</th>
                <th>
                    {{ __('view_compendium.npc.sections.spells.header_schools') }}
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ __('view_compendium.npc.sections.spells.header_schools_tooltip') }}"></i>
                </th>
                <th>
                    {{ __('view_compendium.npc.sections.spells.header_miss_types') }}
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ __('view_compendium.npc.sections.spells.header_miss_types_tooltip') }}"></i>
                </th>
                <th>
                    {{ __('view_compendium.npc.sections.spells.header_counters') }}
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ __('view_compendium.npc.sections.spells.header_counters_tooltip') }}"></i>
                </th>
                <th>
                    {{ __('view_compendium.npc.sections.spells.header_bypasses_immunities') }}
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ __('view_compendium.npc.sections.spells.header_bypasses_immunities_tooltip') }}"></i>
                </th>
                <th>
                    {{ __('view_compendium.npc.sections.spells.header_dispel_type') }}
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ __('view_compendium.npc.sections.spells.header_dispel_type_tooltip') }}"></i>
                </th>
                <th>{{ __('view_compendium.npc.sections.spells.header_mechanic') }}</th>
                <th>{{ __('view_compendium.npc.sections.spells.header_cast_time') }}</th>
                <th>{{ __('view_compendium.npc.sections.spells.header_duration') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($npc->spells as $spell)
                <?php /** @var Spell $spell */ ?>
                @if($spell->hidden_on_map)
                    @continue
                @endif

                <tr>
                    <td class="text-nowrap">@include('common.spell.link', ['spell' => $spell, 'size' => 24])</td>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') }}</td>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_MISS_TYPES, $spell->miss_types_mask, 'spellmisstypes') }}</td>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_COUNTERS, $spell->counters_mask, 'spellcounters') }}</td>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_IMMUNITIES, $spell->bypasses_immunities_mask, 'spellimmunities') }}</td>
                    <td>{{ __($spell->dispel_type) }}</td>
                    <td>{{ __($spell->mechanic) }}</td>
                    <td>{{ $spell->cast_time > 0 ? ($spell->cast_time / 1000) . 's' : '-' }}</td>
                    <td>{{ $spell->duration > 0 ? ($spell->duration / 1000) . 's' : '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted mb-0">{{ __('view_compendium.npc.sections.spells.empty') }}</p>
@endif
