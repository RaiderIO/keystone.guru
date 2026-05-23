<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;

/**
 * @var Npc          $npc
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

    @include('compendium.npc.sections.characteristics')

    @include('compendium.npc.sections.spells')

    @include('compendium.npc.sections.event_feed')
@endsection
