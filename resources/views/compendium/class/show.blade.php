<?php

use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var CharacterClass                        $characterClass
 * @var Dungeon                               $contextDungeon
 * @var Collection<int, Spell>                $spells
 * @var Collection<int, Collection<int, Npc>> $npcsByCharacteristicId
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
                                     class="rounded mr-1"
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
@endsection
