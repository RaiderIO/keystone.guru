<?php

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                              $gameVersion
 * @var Dungeon                                  $model
 * @var string                                   $floorIndex
 * @var array                                    $parameters
 * @var Collection<CharacterClassSpecialization> $characterClassSpecializations
 */

$showStyle = 'regular';

$characterClassSpecializationsSelectOptions = $characterClassSpecializations->groupBy(fn(CharacterClassSpecialization $characterClassSpecialization) => __($characterClassSpecialization->class->name))->mapWithKeys(fn(Collection $specializations, string $className) => [
    $className => $specializations->mapWithKeys(fn(CharacterClassSpecialization $characterClassSpecialization) => [
        $characterClassSpecialization->specialization_id => [
            'icon_url' => $characterClassSpecialization->icon_url,
            'name'     => __($characterClassSpecialization->name),
        ]
    ])
])->toArray();
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.embed.title')])

@section('header-title', __('view_misc.embed.header'))

@section('scripts')
    @parent
    <script type="text/javascript">
        $(function () {
            $('#filter_specializations').on('change', function (){
                $('#ksg_iframe')[0].contentWindow.postMessage({
                    function: 'setFilters',
                    includeSpecIds: $('#filter_specializations').val().join(','),
                    defaultZoom: "1.4",
                    type: "player_spell",
                    minMythicLevel: "2",
                    maxMythicLevel: "99",
                    minPeriod: "1001",
                    maxPeriod: "1006",
                    region: "us",
                    dataType: "enemy_position",
                }, '*')
            })
        });
    </script>
@endsection

@section('content')
    <div class="form-group row">
        <div class="col">
{{--            @include('common.forms.select.imageselectcategories', [--}}
{{--                'id' => 'filter_specializations',--}}
{{--                'name' => 'filter_specializations[]',--}}
{{--                'valuesByCategory' => $characterClassSpecializationsSelectOptions,--}}
{{--                'multiple' => true,--}}
{{--                'liveSearch' => true,--}}
{{--            ])--}}
        </div>
    </div>

    <div class="row justify-content-lg-center">
        <div class="col">
            @if(!empty($parameters))
                <iframe
                    id="ksg_iframe"
                    src="{{ route('dungeon.heatmap.gameversion.embed', array_merge([
                        'gameVersion' => $gameVersion,
                        'dungeon' => $model,
                        'floorIndex' => $floorIndex,
                    ], $parameters)) }}"
                    style="width: 100%; height: 600px; border: none;"></iframe>
            @elseif($showStyle === 'compact')
                <iframe
                    id="ksg_iframe"
                    src="{{ route('dungeon.heatmap.gameversion.embed', [
                        'gameVersion' => $gameVersion,
                        'dungeon' => $model,
                        'floorIndex' => $floorIndex,
                        'style' => 'compact',
                        'headerBackgroundColor' => '#0F0',
                        'mapBackgroundColor' => '#F00',
                        'showEnemyInfo' => 0,
                    ]) }}"
                    style="width: 100%; height: 600px; border: none;"></iframe>
            @endif
        </div>
    </div>
    {{--    <div class="row">--}}
    {{--        <div class="col">--}}
    {{--            <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model, 'pulls' => 1, 'pullsDefaultState' => 0, 'enemyinfo' => 1]) }}"--}}
    {{--                    style="width: 100%; height: 600px; border: none;"></iframe>--}}
    {{--        </div>--}}
    {{--    </div>--}}
@endsection
