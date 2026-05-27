<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Spell           $spell
 * @var Collection<Npc> $npcs
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

    @include('compendium.spell.sections.details')

    @include('compendium.spell.sections.dungeons')

    @include('compendium.spell.sections.npcs')
@endsection
