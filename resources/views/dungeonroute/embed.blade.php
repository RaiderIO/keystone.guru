@extends('layouts.map', ['showAds' => false, 'custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $dungeonroute->title, 'cookieConsent' => false])
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id)->load(['expansion', 'floors']);

$affixes = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes = [-1 => 'Any'];
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
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $floor->id
]])

@section('content')
    <header class="embed"
            style="background-image: url('/images/dungeons/{{$dungeon->expansion->shortname}}/{{$dungeon->key}}.jpg'); background-size: cover;">
        <div class="row no-gutters">
            <div class="col-8">
                <h4>
                    <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                       target="_blank">{{ $dungeonroute->title }}</a>
                </h4>
            </div>
            <div class="col-4">
                <a href="{{ route('home') }}">
                    <h4 class="text-right">
                        {{ __('Keystone.guru') }}
                    </h4>
                </a>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-4">
                <div class="embed-header-subtitle">
                    {!! $dungeonroute->getSubHeaderHtml() !!}
                </div>
            </div>
            <div class="col-4">
                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                @if($dungeon->floors()->count() > 1)
                    {!! Form::select('map_floor_selection', [__('Select floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                @endif
            </div>
            <div class="col-3">
                {!! Form::select('affixes[]', $affixes, $selectedAffixes,
                    ['id' => 'affixes',
                    'class' => 'form-control affixselect selectpicker',
                    'multiple' => 'multiple',
                    'title' => __('Affixes'),
                    'readonly' => 'readonly',
                    'data-selected-text-format' => 'count > 1',
                    'data-count-selected-text' => __('{0} affixes selected')]) !!}
            </div>
            <div class="col-1">
                <div id="embed_copy_mdt_string" class="btn btn-primary float-right" data-toggle="tooltip"
                     title="{{ __('Copy MDT string') }}">
                    <i class="fas fa-file-export"></i>
                </div>
                <div id="embed_copy_mdt_string_loader" class="btn btn-primary float-right" style="display: none;">
                    <i class="fas fa-circle-notch fa-spin"></i>
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
            'noUI' => true,
            'defaultZoom' => 1,
            'floorId' => $floor->id,
            'showAttribution' => false,
            'hiddenMapObjectGroups' => [
                'enemypack',
                'killzonepath'
            ],
            'show' => [
                'share' => []
            ]
        ])
    </div>
@endsection