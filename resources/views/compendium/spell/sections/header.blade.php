<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 */
?>
<div class="compendium_identity">
    <img src="{{ $spell->icon_url }}"
         width="88" height="88"
         alt="{{ __($spell->name) }}"
         loading="lazy"
         class="compendium_identity_portrait"/>
    <div class="compendium_identity_body">
        <h2 class="compendium_identity_title">{{ __($spell->name) }}</h2>
        <div class="compendium_identity_meta">
            @if($spell->aura)
                <span class="badge text-bg-info">
                    {{ __('view_compendium.spell.sections.header.aura') }}
                </span>
            @endif
            @if($spell->debuff)
                <span class="badge text-bg-danger">
                    {{ __('view_compendium.spell.sections.header.debuff') }}
                </span>
            @endif
            @if($spell->schools_mask > 0)
                <span class="compendium_chip">
                    {{ Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask, 'spellschools') }}
                </span>
            @endif
            @if($spell->informative_dispel_type)
                <span class="compendium_chip">
                    {{ $spell->informative_dispel_type }}
                </span>
            @endif
            @if($spell->mechanic)
                <span class="compendium_chip">
                    {{ __($spell->mechanic) }}
                </span>
            @endif
        </div>
    </div>
    <div class="compendium_identity_actions">
        <a href="{{ $spell->wowhead_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary" data-wh-icon-size="small">
            {{ __('view_compendium.spell.show.wowhead') }}
            <i class="fas fa-external-link-alt ms-1"></i>
        </a>
    </div>
</div>
