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
<div class="row mb-4">
    <div class="col-auto">
        <img src="{{ ksgAsset($npc->enemy_portrait_url) }}"
             width="64" height="64"
             alt="{{ __($npc->name) }}"
             loading="lazy"
             class="rounded"/>
    </div>
    <div class="col">
        <h2 class="mb-1">{{ __($npc->name) }}</h2>
        <div>
            <span class="badge {{ $classificationBadge }} me-1">
                {{ __($npc->classification->name) }}
            </span>
            @if($npc->level)
                <span class="badge text-bg-secondary me-1">
                    {{ __('view_compendium.npc.sections.header.level') }} {{ $npc->level }}
                </span>
            @endif
            @if($currentNpcHealth)
                <span class="badge text-bg-secondary me-1">
                    {{ number_format($currentNpcHealth->health) }} HP
                </span>
            @endif
            <span class="badge text-bg-secondary me-1">
                {{ __(sprintf('npcaggressiveness.%s', $npc->aggressiveness)) }}
            </span>
            @if($npc->type)
                <span class="badge text-bg-secondary me-1">
                    {{ $npc->type->type }}
                </span>
            @endif
{{--            @if($npc->class)--}}
{{--                <span class="badge text-bg-secondary me-1">--}}
{{--                    {{ __(sprintf('npcclasses.%s', $npc->class->key)) }}--}}
{{--                </span>--}}
{{--            @endif--}}
            @foreach($flags as $flag)
                <span class="badge text-bg-warning me-1">{{ $flag }}</span>
            @endforeach
        </div>
    </div>
</div>
