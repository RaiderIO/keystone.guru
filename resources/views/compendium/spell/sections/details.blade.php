<?php

use App\Models\Spell\Spell;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 * Rendered as a key-value register instead of a one-row, eight-column table.
 *
 * @var Spell $spell
 */

$details = [
    [
        'label'   => __('view_compendium.spell.sections.details.header_schools'),
        'tooltip' => __('view_compendium.spell.sections.details.header_schools_tooltip'),
        'value'   => Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') ?: '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_miss_types'),
        'tooltip' => __('view_compendium.spell.sections.details.header_miss_types_tooltip'),
        'value'   => Spell::maskToReadableString(Spell::ALL_MISS_TYPES, $spell->miss_types_mask, 'spellmisstypes') ?: '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_counters'),
        'tooltip' => __('view_compendium.spell.sections.details.header_counters_tooltip'),
        'value'   => Spell::maskToReadableString(Spell::ALL_COUNTERS, $spell->counters_mask, 'spellcounters') ?: '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_bypasses_immunities'),
        'tooltip' => __('view_compendium.spell.sections.details.header_bypasses_immunities_tooltip'),
        'value'   => Spell::maskToReadableString(Spell::ALL_IMMUNITIES, $spell->bypasses_immunities_mask, 'spellimmunities') ?: '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_dispel_type'),
        'tooltip' => __('view_compendium.spell.sections.details.header_dispel_type_tooltip'),
        'value'   => $spell->dispel_type ? __($spell->dispel_type) : '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_mechanic'),
        'tooltip' => null,
        'value'   => $spell->mechanic ? __($spell->mechanic) : '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_cast_time'),
        'tooltip' => null,
        'value'   => $spell->cast_time > 0 ? ($spell->cast_time / 1000) . 's' : '-',
    ],
    [
        'label'   => __('view_compendium.spell.sections.details.header_duration'),
        'tooltip' => null,
        'value'   => $spell->duration > 0 ? ($spell->duration / 1000) . 's' : '-',
    ],
];
?>
<dl class="compendium_kv">
    @foreach($details as $detail)
        <div class="compendium_kv_item">
            <dt>
                {{ $detail['label'] }}
                @if($detail['tooltip'])
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                       title="{{ $detail['tooltip'] }}"></i>
                @endif
            </dt>
            <dd>{{ $detail['value'] }}</dd>
        </div>
    @endforeach
</dl>
