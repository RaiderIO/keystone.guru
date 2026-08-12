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
 * @var Collection<int, Dungeon> $gameVersionDungeons
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbs'       => 'compendium.class.show',
    'breadcrumbsParams' => [$characterClass, $contextDungeon],
    'title'             => __('view_compendium.class.show.title', ['name' => __($characterClass->name)]),
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon' => $dungeon,
        ])
    ]),
])

@section('content')
    {{-- Header --}}
    <div class="compendium_identity">
        <img src="{{ $characterClass->icon_url }}"
             width="88" height="88"
             alt="{{ __($characterClass->name) }}"
             loading="lazy"
             class="compendium_identity_portrait"/>
        <div class="compendium_identity_body">
            <h2 class="compendium_identity_title">{{ __($characterClass->name) }}</h2>
            <div class="compendium_identity_meta">
                <span class="compendium_chip">{{ __($contextDungeon->name) }}</span>
            </div>
        </div>
    </div>

    {{-- Spell → Characteristic → Affected NPCs table --}}
    @if($spells->isEmpty())
        <p class="text-muted">{{ __('view_compendium.class.show.no_spells') }}</p>
    @else
        <div class="table-responsive">
            <table class="compendium_table">
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
                        <td class="text-nowrap">@include('common.spell.link', ['spell' => $spell])</td>
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
            <div class="compendium_record_section @if($loop->first) mt-4 @endif">
                <div class="compendium_record_label">
                    @if($loop->first)
                        {{ __('view_compendium.class.show.counters.title') }}
                    @endif
                </div>
                <div>
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
                        <p class="text-muted mb-0">{{ __('view_compendium.class.show.counters.no_spells') }}</p>
                    @else
                        @include('compendium.class.sections.spell-npc-table', [
                            'tableSpells'        => $counterSpells,
                            'tableNpcsBySpellId' => $npcsBySpellId,
                            'tableSpellHeader'   => 'view_compendium.class.show.counters.table_header_spell',
                            'tableNpcsHeader'    => 'view_compendium.class.show.counters.table_header_npcs',
                        ])
                    @endif
                </div>
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
        <div class="compendium_record_section">
            <div class="compendium_record_label">
                {{ __('view_compendium.class.show.reflect.title') }}
                <div class="compendium_record_label_sub">
                    {{ __('view_compendium.class.show.reflect.description') }}
                </div>
            </div>
            <div>
                <h5 class="mb-2">
                    <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $reflectSection['iconName'])) }}"
                         width="20" height="20"
                         loading="lazy"
                         class="rounded me-1"
                         alt="{{ __('view_compendium.class.show.reflect.title') }}"/>{{ __('view_compendium.class.show.reflect.title') }}
                </h5>

                @if($reflectSpells->isEmpty())
                    <p class="text-muted mb-0">{{ __('view_compendium.class.show.reflect.no_spells') }}</p>
                @else
                    @include('compendium.class.sections.spell-npc-table', [
                        'tableSpells'        => $reflectSpells,
                        'tableNpcsBySpellId' => $reflectNpcsBySpellId,
                        'tableSpellHeader'   => 'view_compendium.class.show.reflect.table_header_spell',
                        'tableNpcsHeader'    => 'view_compendium.class.show.reflect.table_header_npcs',
                    ])
                @endif
            </div>
        </div>
    @endif
@endsection
