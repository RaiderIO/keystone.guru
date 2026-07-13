<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 */
?>
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.spell.sections.details.title') }}</h4>
        <div class="table-responsive">
            <table class="table table-sm mt-2">
                <thead>
                <tr>
                    <th>
                        {{ __('view_compendium.spell.sections.details.header_schools') }}
                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                           title="{{ __('view_compendium.spell.sections.details.header_schools_tooltip') }}"></i>
                    </th>
                    <th>
                        {{ __('view_compendium.spell.sections.details.header_miss_types') }}
                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                           title="{{ __('view_compendium.spell.sections.details.header_miss_types_tooltip') }}"></i>
                    </th>
                    <th>
                        {{ __('view_compendium.spell.sections.details.header_dispel_type') }}
                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                           title="{{ __('view_compendium.spell.sections.details.header_dispel_type_tooltip') }}"></i>
                    </th>
                    <th>{{ __('view_compendium.spell.sections.details.header_mechanic') }}</th>
                    <th>{{ __('view_compendium.spell.sections.details.header_cast_time') }}</th>
                    <th>{{ __('view_compendium.spell.sections.details.header_duration') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') ?: '-' }}</td>
                    <td>{{ Spell::maskToReadableString(Spell::ALL_MISS_TYPES, $spell->miss_types_mask, 'spellmisstypes') ?: '-' }}</td>
                    <td>{{ $spell->dispel_type ? __($spell->dispel_type) : '-' }}</td>
                    <td>{{ $spell->mechanic ? __($spell->mechanic) : '-' }}</td>
                    <td>{{ $spell->cast_time > 0 ? ($spell->cast_time / 1000) . 's' : '-' }}</td>
                    <td>{{ $spell->duration > 0 ? ($spell->duration / 1000) . 's' : '-' }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
