<?php

use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitionInterface;
use Illuminate\Support\Collection;

/**
 * @var CharacterClass                        $characterClass
 * @var Dungeon                               $contextDungeon
 * @var Collection<int, Spell>                $spells
 * @var Collection<int, Collection<int, Npc>> $npcsByCharacteristicId
 * @var array<int, array{
 *     definition: SpellCounterDefinitionInterface,
 *     raceName: string|null,
 *     spells: Collection<int, Spell>,
 *     npcsBySpellId: Collection<int, Collection<int, Npc>>,
 * }> $counterSections
 * @var array{
 *     iconName: string,
 *     spells: Collection<int, Spell>,
 *     npcsBySpellId: Collection<int, Collection<int, Npc>>,
 * }|null $reflectSection
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbs'       => 'compendium.class.show',
    'breadcrumbsParams' => [$characterClass, $contextDungeon],
    'title'             => __('view_compendium.class.show.title', ['name' => __($characterClass->name)]),
])

@section('content')
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-auto">
            <img src="{{ $characterClass->icon_url }}"
                 width="64" height="64"
                 alt="{{ __($characterClass->name) }}"
                 loading="lazy"
                 class="rounded"/>
        </div>
        <div class="col">
            <h2 class="mb-1">{{ __($characterClass->name) }}</h2>
        </div>
    </div>

    {{-- Spell → Characteristic → Affected NPCs table --}}
    @if($spells->isEmpty())
        <p class="text-muted">{{ __('view_compendium.class.show.no_spells') }}</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                <tr>
                    <th width="25%">{{ __('view_compendium.class.show.table_header_spell') }}</th>
                    <th width="20%">{{ __('view_compendium.class.show.table_header_characteristic') }}</th>
                    <th width="55%">{{ __('view_compendium.class.show.table_header_npcs') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($spells as $spell)
                    <?php /** @var Spell $spell */ ?>
                    <?php $affectedNpcs = $npcsByCharacteristicId->get($spell->characteristic_id, collect()); ?>
                    <tr>
                        <td>@include('common.spell.link', ['spell' => $spell])</td>
                        <td>
                            @if($spell->characteristic)
                                <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $spell->characteristic->icon_name)) }}"
                                     width="20" height="20"
                                     loading="lazy"
                                     class="rounded me-1"
                                     alt="{{ __($spell->characteristic->name) }}"/>{{ __($spell->characteristic->name) }}
                            @endif
                        </td>
                        <td>
                            @if($affectedNpcs->isEmpty())
                                <span class="text-muted">{{ __('view_compendium.class.show.no_npcs') }}</span>
                            @else
                                @foreach($affectedNpcs as $npc)
                                    <?php /** @var Npc $npc */ ?>
                                    @include('common.npc.link', ['npc' => $npc])@if(!$loop->last), @endif
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Counterable abilities (Vanish / Shadowmeld / ...) --}}
    @if(!empty($counterSections))
        <h3 class="mt-4">{{ __('view_compendium.class.show.counters.title') }}</h3>

        @foreach($counterSections as $counterSection)
            <?php
            /** @var SpellCounterDefinitionInterface $definition */
            $definition = $counterSection['definition'];
            $raceName   = $counterSection['raceName'];
            /** @var Collection<int, Spell> $counterSpells */
            $counterSpells = $counterSection['spells'];
            /** @var Collection<int, Collection<int, Npc>> $npcsBySpellId */
            $npcsBySpellId = $counterSection['npcsBySpellId'];
            $counterKey    = Spell::ALL_COUNTERS[$definition->getCounterBit()];
            ?>
            <div class="mb-4">
                <h5 class="mb-2">
                    <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $definition->getIconName())) }}"
                         width="20" height="20"
                         loading="lazy"
                         class="rounded me-1"
                         alt="{{ __('spellcounters.' . $counterKey) }}"/>{{ __('spellcounters.' . $counterKey) }}
                    @if($raceName !== null)
                        <span class="badge text-bg-secondary ms-1">
                            {{ __('view_compendium.class.show.counters.racial', ['race' => __($raceName)]) }}
                        </span>
                    @endif
                </h5>

                @if($counterSpells->isEmpty())
                    <p class="text-muted">{{ __('view_compendium.class.show.counters.no_spells') }}</p>
                @else
                    @include('compendium.class.sections.spell-npc-table', [
                        'tableSpells'        => $counterSpells,
                        'tableNpcsBySpellId' => $npcsBySpellId,
                        'tableSpellHeader'   => 'view_compendium.class.show.counters.table_header_spell',
                        'tableNpcsHeader'    => 'view_compendium.class.show.counters.table_header_npcs',
                    ])
                @endif
            </div>
        @endforeach
    @endif

    {{-- Reflectable spells (Spell Reflection) --}}
    @if($reflectSection !== null)
        <?php
        /** @var Collection<int, Spell> $reflectSpells */
        $reflectSpells = $reflectSection['spells'];
        /** @var Collection<int, Collection<int, Npc>> $reflectNpcsBySpellId */
        $reflectNpcsBySpellId = $reflectSection['npcsBySpellId'];
        ?>
        <h3 class="mt-4">
            <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $reflectSection['iconName'])) }}"
                 width="24" height="24"
                 loading="lazy"
                 class="rounded me-1"
                 alt="{{ __('view_compendium.class.show.reflect.title') }}"/>{{ __('view_compendium.class.show.reflect.title') }}
        </h3>
        <p class="text-muted">{{ __('view_compendium.class.show.reflect.description') }}</p>

        @if($reflectSpells->isEmpty())
            <p class="text-muted">{{ __('view_compendium.class.show.reflect.no_spells') }}</p>
        @else
            @include('compendium.class.sections.spell-npc-table', [
                'tableSpells'        => $reflectSpells,
                'tableNpcsBySpellId' => $reflectNpcsBySpellId,
                'tableSpellHeader'   => 'view_compendium.class.show.reflect.table_header_spell',
                'tableNpcsHeader'    => 'view_compendium.class.show.reflect.table_header_npcs',
            ])
        @endif
    @endif
@endsection
