<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcHealth;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\Spell\Spell;

/**
 * @var Npc          $npc
 * @var NpcHealth|null $currentNpcHealth
 */

$classificationBadge = match ($npc->classification->key ?? '') {
    NpcClassification::NPC_CLASSIFICATION_BOSS,
    NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS => 'badge-danger',
    NpcClassification::NPC_CLASSIFICATION_RARE        => 'badge-warning',
    NpcClassification::NPC_CLASSIFICATION_ELITE       => 'badge-info',
    default                                           => 'badge-secondary',
};

$flags = [];
foreach (['dangerous', 'truesight', 'bursting', 'bolstering', 'sanguine', 'runs_away_in_fear'] as $flag) {
    if ($npc->{$flag}) {
        $flags[] = __('view_admin.npc.edit.' . $flag);
    }
}
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc],
    'title'             => __('view_compendium.npc.show.title', ['name' => __($npc->name)]),
])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            if (typeof $WowheadPower !== 'undefined') {
                $WowheadPower.refreshLinks();
            }
        });
    </script>
@endsection

@section('content')
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
                <span class="badge {{ $classificationBadge }} mr-1">
                    {{ __($npc->classification->name) }}
                </span>
                @if($npc->level)
                    <span class="badge badge-secondary mr-1">
                        {{ __('view_compendium.npc.show.level') }} {{ $npc->level }}
                    </span>
                @endif
                @if($currentNpcHealth)
                    <span class="badge badge-secondary mr-1">
                        {{ number_format($currentNpcHealth->health) }} HP
                    </span>
                @endif
                <span class="badge badge-secondary mr-1">
                    {{ __(sprintf('npcaggressiveness.%s', $npc->aggressiveness)) }}
                </span>
                @if($npc->type)
                    <span class="badge badge-secondary mr-1">
                        {{ $npc->type->type }}
                    </span>
                @endif
                @if($npc->class)
                    <span class="badge badge-secondary mr-1">
                        {{ __(sprintf('npcclasses.%s', $npc->class->key)) }}
                    </span>
                @endif
                @foreach($flags as $flag)
                    <span class="badge badge-warning mr-1">{{ $flag }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Characteristics --}}
    <div class="row mb-4">
        <div class="col">
            <h4>{{ __('view_compendium.npc.show.section_characteristics') }}</h4>
            @if($npc->characteristics->isNotEmpty())
                <div>
                    @foreach($npc->characteristics as $characteristic)
                        <?php /** @var Characteristic $characteristic */ ?>
                        <span class="badge badge-info mr-1 mb-1">
                            {{ __($characteristic->name) }}
                        </span>
                    @endforeach
                </div>
            @else
                <p class="text-muted">{{ __('view_compendium.npc.show.no_characteristics') }}</p>
            @endif
        </div>
    </div>

    {{-- Spells --}}
    <div class="row mb-4">
        <div class="col">
            <h4>{{ __('view_compendium.npc.show.section_spells') }}</h4>
            @if($npc->spells->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mt-2">
                        <thead>
                        <tr>
                            <th>{{ __('view_compendium.npc.show.spell_header_name') }}</th>
                            <th>
                                {{ __('view_compendium.npc.show.spell_header_schools') }}
                                <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                                   title="{{ __('view_compendium.npc.show.spell_header_schools_tooltip') }}"></i>
                            </th>
                            <th>
                                {{ __('view_compendium.npc.show.spell_header_miss_types') }}
                                <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                                   title="{{ __('view_compendium.npc.show.spell_header_miss_types_tooltip') }}"></i>
                            </th>
                            <th>
                                {{ __('view_compendium.npc.show.spell_header_dispel_type') }}
                                <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                                   title="{{ __('view_compendium.npc.show.spell_header_dispel_type_tooltip') }}"></i>
                            </th>
                            <th>{{ __('view_compendium.npc.show.spell_header_mechanic') }}</th>
                            <th>{{ __('view_compendium.npc.show.spell_header_cast_time') }}</th>
                            <th>{{ __('view_compendium.npc.show.spell_header_duration') }}</th>
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
                <p class="text-muted">{{ __('view_compendium.npc.show.no_spells') }}</p>
            @endif
        </div>
    </div>

    {{-- Dungeons --}}
{{--    <div class="mb-4">--}}
{{--        <h4>{{ __('view_compendium.npc.show.section_dungeons') }}</h4>--}}
{{--        @if($npc->dungeons->isNotEmpty())--}}
{{--            @include('common.dungeon.list', [--}}
{{--                'dungeons' => $npc->dungeons->sortBy(fn(Dungeon $d) => __($d->name)),--}}
{{--                'width' => 'col-auto',--}}
{{--            ])--}}
{{--        @else--}}
{{--            <p class="text-muted">{{ __('view_compendium.npc.show.no_dungeons') }}</p>--}}
{{--        @endif--}}
{{--    </div>--}}
@endsection
