<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcHealth;

/**
 * @var Npc            $npc
 * @var NpcHealth|null $currentNpcHealth
 */

$classificationBadge = match ($npc->classification->key ?? '') {
    NpcClassification::NPC_CLASSIFICATION_BOSS,
    NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS => 'text-bg-danger',
    NpcClassification::NPC_CLASSIFICATION_RARE        => 'text-bg-warning',
    NpcClassification::NPC_CLASSIFICATION_ELITE       => 'text-bg-info',
    default                                           => 'text-bg-secondary',
};

$flags = [];
foreach (['dangerous', 'truesight', /*'bursting', 'bolstering', 'sanguine',*/ 'runs_away_in_fear'] as $flag) {
    if ($npc->{$flag}) {
        $flags[] = __('view_admin.npc.edit.' . $flag);
    }
}
?>
<div class="compendium_identity">
    <img src="{{ ksgAsset($npc->enemy_portrait_url) }}"
         width="88" height="88"
         alt="{{ __($npc->name) }}"
         loading="lazy"
         class="compendium_identity_portrait"/>
    <div class="compendium_identity_body">
        <h2 class="compendium_identity_title">{{ __($npc->name) }}</h2>
        <div class="compendium_identity_meta">
            <span class="badge {{ $classificationBadge }}">
                {{ __($npc->classification->name) }}
            </span>
            @foreach($flags as $flag)
                <span class="badge text-bg-warning">{{ $flag }}</span>
            @endforeach
            @if($npc->level)
                <span class="compendium_chip">
                    <span class="compendium_chip_label">{{ __('view_compendium.npc.sections.header.level') }}</span>
                    {{ $npc->level }}
                </span>
            @endif
            @if($currentNpcHealth)
                <span class="compendium_chip">
                    <span class="compendium_chip_label">HP</span>
                    {{ number_format($currentNpcHealth->health) }}
                </span>
            @endif
            <span class="compendium_chip">
                {{ __(sprintf('npcaggressiveness.%s', $npc->aggressiveness)) }}
            </span>
            @if($npc->type)
                <span class="compendium_chip">
                    {{ $npc->type->type }}
                </span>
            @endif
{{--            @if($npc->class)--}}
{{--                <span class="compendium_chip">--}}
{{--                    {{ __(sprintf('npcclasses.%s', $npc->class->key)) }}--}}
{{--                </span>--}}
{{--            @endif--}}
        </div>
    </div>
    <div class="compendium_identity_actions">
        <a href="{{ $npc->wowhead_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary" data-wh-icon-size="small">
            {{ __('view_compendium.npc.show.wowhead') }}
            <i class="fas fa-external-link-alt ms-1"></i>
        </a>
    </div>
</div>
