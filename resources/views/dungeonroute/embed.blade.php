@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => __('views/dungeonroute.embed.title', ['routeTitle' => $dungeonroute->title]),
    'bodyClass' => 'overflow-hidden',
    'cookieConsent' => false
])
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
/** @var $embedOptions array */
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id)->load(['expansion', 'floors']);

$affixes = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('views/dungeonroute.embed.any')];
    $selectedAffixes = -1;
}
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])
@endsection

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map']
])

@include('common.general.inline', ['path' => 'common/maps/embedtopbar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'switchDungeonFloorSelect' => '#map_floor_selection_dropdown',
    'defaultSelectedFloorId' => $floor->id
]])

@section('content')
    <header class="header_embed"
            style="background-image: url('/images/dungeons/{{$dungeon->expansion->shortname}}/{{$dungeon->key}}.jpg'); background-size: cover;">
        <div class="row no-gutters py-2">
            <div class="col-8 pt-2">
                <div class="row no-gutters">
                    <div class="col">
                        <a class="text-white" href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                           target="_blank">
                            <h4 class="mb-0">
                                {{ $dungeonroute->title }}
                            </h4>
                        </a>
                    </div>
                </div>
                <div class="row no-gutters">
                    <div class="col">
                        {{ __('views/dungeonroute.embed.create_or_view_at') }}
                        <a class="text-white" href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                           target="_blank">
                            {{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-4 text-right">
                <a href="{{ route('home') }}" target="_blank">
                    <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                         height="44px;" width="200px;">
                </a>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-auto">
                <div class="embed-header-subtitle">
                    <?php // This is normally in the pulls sidebar - but for embedding it's in the header - see pulls.blade.php ?>
                    <div id="edit_route_enemy_forces_container"></div>
                </div>
            </div>
            <div class="col-md-auto d-md-flex d-none pl-2">
                <?php
                $mostRelevantAffixGroup = $dungeonroute->getMostRelevantAffixGroup();
                ?>
                @include('common.affixgroup.affixgroup', ['affixgroup' => $mostRelevantAffixGroup, 'showText' => false, 'class' => 'w-100'])
            </div>
            <div class="col">
            </div>
            <div class="col-auto pr-2">
                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                @if($dungeon->floors()->count() > 1)
                    {!! Form::select('map_floor_selection', [__('views/dungeonroute.embed.select_floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                @endif
            </div>
            <div class="col-auto">
                <div id="embed_copy_mdt_string" class="btn btn-primary float-right">
                    <i class="fas fa-file-export"></i> {{ __('views/dungeonroute.embed.copy_mdt_string') }}
                </div>
                <div id="embed_copy_mdt_string_loader" class="btn btn-primary float-right" disabled
                     style="display: none;">
                    <i class="fas fa-circle-notch fa-spin"></i> {{ __('views/dungeonroute.embed.copy_mdt_string') }}
                </div>
            </div>
        </div>
    </header>

    <div class="wrapper embed_wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'embed' => true,
            'edit' => false,
            'echo' => false,
            'defaultZoom' => 1,
            'floorId' => $floor->id,
            'showAttribution' => false,
            'hiddenMapObjectGroups' => [
                'enemypack'
            ],
            'show' => [
                'header' => false,
                'share' => [],
                'controls' => [
                    'pulls' => $embedOptions['pulls'],
                    'pullsDefaultState' => $embedOptions['pullsDefaultState'],
                    'pullsHideOnMove' => $embedOptions['pullsHideOnMove'],
                    'enemyinfo' => $embedOptions['enemyinfo'],
                ],
            ]
        ])
    </div>
@endsection
