<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;

/**
 * @var Npc $npc
 */
?>
{{-- Spells --}}
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.npc.sections.spells.title') }}</h4>
        @if($npc->spells->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm mt-2">
                    <thead>
                    <tr>
                        <th>{{ __('view_compendium.npc.sections.spells.header_name') }}</th>
                        <th>
                            {{ __('view_compendium.npc.sections.spells.header_schools') }}
                            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                               title="{{ __('view_compendium.npc.sections.spells.header_schools_tooltip') }}"></i>
                        </th>
                        <th>
                            {{ __('view_compendium.npc.sections.spells.header_miss_types') }}
                            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                               title="{{ __('view_compendium.npc.sections.spells.header_miss_types_tooltip') }}"></i>
                        </th>
                        <th>
                            {{ __('view_compendium.npc.sections.spells.header_dispel_type') }}
                            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
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
                        <tr>
                            <td>
                                <a href="{{ $spell->wowhead_url }}" data-wh-icon-size="small">
                                    <img src="{{ $spell->icon_url }}" width="24" height="24" loading="lazy"/>
                                    {{ __($spell->name) }}
                                </a>
                            </td>
                            <td>{{ Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') }}</td>
                            <td>{{ Spell::maskToReadableString(Spell::ALL_MISS_TYPES, $spell->miss_types_mask, 'spellmisstypes') }}</td>
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
            <p class="text-muted">{{ __('view_compendium.npc.sections.spells.empty') }}</p>
        @endif
    </div>
</div>
