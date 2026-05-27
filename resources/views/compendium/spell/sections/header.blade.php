<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 */
?>
<div class="row mb-4">
    <div class="col-auto">
        <img src="{{ $spell->icon_url }}"
             width="64" height="64"
             alt="{{ __($spell->name) }}"
             loading="lazy"
             class="rounded"/>
    </div>
    <div class="col">
        <h2 class="mb-1">{{ __($spell->name) }}</h2>
        <div>
            @if($spell->schools_mask > 0)
                <span class="badge badge-secondary mr-1">
                    {{ Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') }}
                </span>
            @endif
            @if($spell->dispel_type)
                <span class="badge badge-secondary mr-1">
                    {{ __($spell->dispel_type) }}
                </span>
            @endif
            @if($spell->mechanic)
                <span class="badge badge-secondary mr-1">
                    {{ __($spell->mechanic) }}
                </span>
            @endif
            @if($spell->aura)
                <span class="badge badge-info mr-1">
                    {{ __('view_compendium.spell.sections.header.aura') }}
                </span>
            @endif
            @if($spell->debuff)
                <span class="badge badge-danger mr-1">
                    {{ __('view_compendium.spell.sections.header.debuff') }}
                </span>
            @endif
        </div>
        <div class="mt-2">
            <a href="{{ $spell->wowhead_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary" data-wh-icon-size="small">
                {{ __('view_compendium.spell.show.wowhead') }}
                <i class="fas fa-external-link-alt ml-1"></i>
            </a>
        </div>
    </div>
</div>
