<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;

/**
 * @var Npc            $npc
 * @var NpcHealth|null $currentNpcHealth
 */
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
    @include('compendium.npc.sections.header')

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.npc.sections.characteristics.title') }}
            <i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top"
               title="{{ __('view_compendium.npc.sections.characteristics.tooltip') }}"></i>
        </div>
        <div>
            @include('compendium.npc.sections.characteristics')
        </div>
    </div>

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.npc.sections.spells.title') }}
        </div>
        <div>
            @include('compendium.npc.sections.spells')
        </div>
    </div>

    <div class="compendium_record_section">
        <div class="compendium_record_label">
            {{ __('view_compendium.npc.sections.event_feed.title') }}
        </div>
        <div>
            @include('compendium.npc.sections.event_feed')
        </div>
    </div>
@endsection
