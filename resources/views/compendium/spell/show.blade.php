<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Spell                                                  $spell
 * @var Collection<int, Npc>                                   $npcs
 * @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> $eventFeed
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbs'       => 'compendium.spell.show',
    'breadcrumbsParams' => [$spell],
    'title'             => __('view_compendium.spell.show.title', ['name' => __($spell->name)]),
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
    @include('compendium.spell.sections.header')

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.spell.sections.details.title') }}
        </div>
        <div>
            @include('compendium.spell.sections.details')
        </div>
    </div>

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.spell.sections.dungeons.title') }}
        </div>
        <div>
            @include('compendium.spell.sections.dungeons')
        </div>
    </div>

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.spell.sections.npcs.title') }}
        </div>
        <div>
            @include('compendium.spell.sections.npcs')
        </div>
    </div>

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.spell.sections.event_feed.title') }}
        </div>
        <div>
            @include('compendium.spell.sections.event_feed')
        </div>
    </div>
@endsection
